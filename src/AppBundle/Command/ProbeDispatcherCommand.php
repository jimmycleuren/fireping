<?php
namespace AppBundle\Command;

use AppBundle\Instruction\Instruction;
use AppBundle\Instruction\InstructionBuilder;
use AppBundle\Probe\EchoPoster;
use AppBundle\Probe\HttpPoster;
use AppBundle\Probe\Message;
use AppBundle\Probe\MessageQueueHandler;
use AppBundle\Probe\MessageQueue;
use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class ProbeDispatcherCommand
 * @package AppBundle\Command
 */
class ProbeDispatcherCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $processes = array();

    /**
     * @var array
     */
    protected $inputs = array();

    /** @var \SplQueue */
    protected $queue;

    /** @var boolean */
    protected $queueLock;

    /** @var MessageQueueHandler */
    protected $queueHandler;

    protected $workerLimit;

    protected function configure()
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
            ->addOption(
                'workers-limit',
                'w',
                InputOption::VALUE_REQUIRED,
                'How many workers can be created at most?',
                50
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $id = $this->getContainer()->getParameter('slave.id');
        $poster = new EchoPoster("https://smokeping-dev.cegeka.be/api/slaves/$id/result");
        $this->queueHandler = new MessageQueueHandler($poster);
        $this->queueHandler->addQueue(new MessageQueue('data'));
        $this->queueHandler->addQueue(new MessageQueue('exceptions'));

        $this->workerLimit = $input->getOption('workers-limit');

        $this->queue = new \SplQueue();
        $pid = getmypid();
        $now = date('l jS \of F Y h:i:s A');

        $this->log($pid, "Started on $now");

        $loop = Factory::create();

        $probeStore = $this->getContainer()->get('probe_store');

        $loop->addPeriodicTimer(15, function () use ($pid, $probeStore, $logger) {
            $this->log($pid, "Synchronizing ProbeStore Asynchronously.");
            $probeStore->async($logger);
        });

        $loop->addPeriodicTimer(1, function () use ($probeStore) {
            foreach ($probeStore->getProbes() as $probe) {
                /* @var $probe ProbeDefinition */
                $now = time();
                $remainder = $now % $probe->getStep();

                if (!$remainder) {

                    $instructionBuilder = $this->getContainer()->get('instruction_builder');
                    $instructions = $instructionBuilder->create($probe);

                    /* @var $instructions Instruction */
                    foreach ($instructions->getChunks() as $instruction) {
                        try {
                            $worker = $this->getWorker();
                        } catch (\Exception $exception) {
                            $this->queueHandler->addMessage('exceptions', new Message(
                                MESSAGE::SERVER_ERROR,
                                'Workers limit reached.',
                                array(
                                    get_class($exception) => $exception->getMessage(),
                                )
                            ));
                            break;
                        }
                        $workerPid = $worker->getPid();
                        $input = $this->getInput($workerPid);
                        $instruction = json_encode($instruction);
                        $this->log(0, "Sending instruction to pid/$workerPid: $instruction");
                        $input->write($instruction);
                    }
                }
            }
        });

        $loop->addPeriodicTimer(1 * 60, function () {
            $x = $this->queue->count();
            $this->log(0, "Queue currently has $x items left to be processed.");
            if (!$this->queueLock) {
                $this->queueLock = true;
                while (!$this->queue->isEmpty()) {
                    $node = $this->queue->shift();
                    try {
                        //$this->postResults($node);
                        echo "Posting results to master: \n" . json_encode($node) . "\n";
                    } catch (TransferException $exception) {
                        $this->queue->unshift($node);
                        $this->queueLock = false;
                        break;
                    }
                }
                $this->queueLock = false;
            } else {
                $this->log(0, "Queue is currently locked.");
            }
        });

        $loop->addPeriodicTimer(1 * 30, function () {
            $this->queueHandler->processQueue('exceptions');
        });

        $loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $pid => $process) {
                try {
                    if ($process) {
                        $process->checkTimeout();
                        $process->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $exception) {
                    $this->cleanup($pid);
                }
            }
        });

        $this->log($pid, "Synchronizing ProbeStore.");
        $probeStore->sync();

        $loop->run();
    }

    /**
     * @param $pid
     * @param $data
     */
    private function log($pid, $data)
    {
        $className = get_class($this);
        $now = date('Y/m/j H:i:s');
        echo "$now $className($pid) $data\n";
    }

    private function handleResponse($type, $data)
    {
        $decoded = json_decode($data, true);

        if (!$decoded) {
            $this->log(0, "Error: could not decode to json: $data");
            return;
        }

        if (!isset($decoded['status'], $decoded['message'], $decoded['body']))
        {
            $this->log(0, "Error: missing key in response:");
            var_dump($decoded);
            return;
        }

        // TODO: Handle different status codes.  Right now, we assume that only data is sent.
        // TODO: Should also handle client and server errors.
        $cleaned = array();
        foreach ($decoded['body'] as $id => $message) {
            if (!isset($message['type'], $message['timestamp'], $message['targets'])) {
                $this->log(0, "Error: missing key in results.");
                var_dump($message);
                continue; // Do not attempt to post incomplete results.
            }
            $cleaned[$id] = $message;
        }
        if ($decoded['status'] == Message::MESSAGE_OK) {
            $this->queueHandler->addMessage('data', new Message(
                Message::MESSAGE_OK,
                'Message OK',
                $cleaned
            ));
        } else {
            $this->queueHandler->addMessage('exceptions', new Message(
                Message::SERVER_ERROR,
                'An error...',
                $decoded
            ));
        }
        $this->queue->enqueue($cleaned);
    }

    /**
     * Posts the formatted results from any probe to the master.
     *
     * @param array $results
     */
    private function postResults(array $results)
    {
        $this->log(0, 'Building Client...');
        $client = new Client();

        $data = json_encode($results);
        $this->log(0, "Attempting to post results: $data");
        try {
            $id = $this->getContainer()->getParameter('slave.id');
            $this->log(0, "Retrieving config for Slave $id");
            $response = $client->post("https://smokeping-dev.cegeka.be/api/slaves/$id/result", [
                'body' => json_encode($results),
            ]);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            $this->log(0, "Response code=$statusCode, body=$body");
        } catch (TransferException $exception) {
            $message = $exception->getMessage();
            $this->log(0, "Exception while pushing results: $message.");
            throw $exception; // TODO: This probably just shouldn't throw any exceptions and instead let the timer handle it.
        }
    }

    /**
     * Get a new Worker process.
     *
     * @return Process
     */
    private function getWorker()
    {
        if (count($this->processes) > $this->workerLimit) {
            throw new \Exception("Worker limit reached.");
        }

        // TODO: Remove verbosity.
        // TODO: Replace absolute path.
        $process = new Process("exec php /var/www/fireping/bin/console app:probe:worker -vvv");
        $input = new InputStream();
        $process->setInput($input);
        $process->setTimeout(180);
        $process->setIdleTimeout(60);
        $process->start(function ($type, $data) use ($process) {
            $pid = $process->getPid();
            $this->handleResponse($type, $data);
            $this->log(0, "Killing Process/$pid");
            $this->cleanup($pid);
        });
        $pid = $process->getPid();
        $this->log(0,"[Process/$pid] Started");

        $this->processes[$pid] = $process;
        $this->inputs[$pid] = $input;

        return $process;
    }

    /**
     * Get or create a new InputStream for a given $id.
     *
     * @param $pid
     * @return mixed
     */
    private function getInput($pid) {
        if (!isset($this->processes[$pid])) {
            throw new \Exception("Process for PID=$pid not found.");
        }

        if (!isset($this->inputs[$pid])) {
            throw new \Exception("Input for PID=$pid not found.");
        }

        return $this->inputs[$pid];
    }

    /**
     * Dereferences old processes and inputs.
     *
     * @param $id
     */
    private function cleanup($id)
    {
        if (isset($this->processes[$id])) {
            $this->processes[$id]->stop(3, SIGINT);
            $this->processes[$id] = null;
            unset($this->processes[$id]);
        }

        if (isset($this->inputs[$id])) {
            $this->inputs[$id] = null;
            unset($this->inputs[$id]);
        }
    }
}