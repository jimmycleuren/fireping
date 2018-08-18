<?php

namespace App\Command;

use App\DependencyInjection\ProbeStore;
use App\DependencyInjection\Queue;
use App\Instruction\Instruction;

use App\Probe\ProbeDefinition;

use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class ProbeDispatcherCommand
 * @package App\Command
 */
class ProbeDispatcherCommand extends ContainerAwareCommand
{
    /** @var array */
    protected $processes = array();

    /** @var array */
    protected $inputs = array();

    protected $numberOfQueues = 10;
    protected $queues;

    protected $workerLimit;

    /** @var KernelInterface */
    protected $kernel;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ProbeStore */
    protected $probeStore;

    protected $trackingGuids = array();

    protected $rcv_buffers = array();

    protected $availableWorkers = array();

    protected $inUseWorkers = array();

    protected $workersNeeded;

    protected $initWorkers;
    protected $minimumAvailableWorkers;
    protected $maximumWorkers;
    protected $highWorkersThreshold;
    protected $inUsePeak;
    protected $maxRuntime;

    protected $startTimes      = array();
    protected $expectedRuntime = array();

    protected $loop;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
            ->addOption(
                'workers',
                'w',
                InputOption::VALUE_REQUIRED,
                'Specifies the amount of workers to start out with.',
                50
            )
            ->addOption(
                'minimum-available-workers',
                'min',
                InputOption::VALUE_REQUIRED,
                'Specifies the minimum amount of available workers at all times.',
                5
            )
            ->addOption(
                'maximum-workers',
                'max',
                InputOption::VALUE_REQUIRED,
                'Specifies the maximum amount of workers that can ever be created.',
                200
            )
            ->addOption(
                'high-workers-threshold',
                'high',
                InputOption::VALUE_REQUIRED,
                'Specifies when the master should alert if too many workers are needed. (Should be lower than maximum-workers.)',
                150
            )
            ->addOption(
                'max-runtime',
                'runtime',
                InputOption::VALUE_REQUIRED,
                'The amount of seconds the command can run before terminating itself',
                0
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->kernel     = $this->getContainer()->get('kernel');
        $this->probeStore = $this->getContainer()->get('probe_store');

        $this->initWorkers             = $input->getOption('workers');
        $this->minimumAvailableWorkers = $input->getOption('minimum-available-workers');
        $this->maximumWorkers          = $input->getOption('maximum-workers');
        $this->highWorkersThreshold    = $input->getOption('high-workers-threshold');
        $this->maxRuntime              = $input->getOption('max-runtime');

        if ($this->highWorkersThreshold >= $this->maximumWorkers) {
            throw new \Exception("High workers threshold value must be less than maximum workers value.");
        }

        if ( !getenv('SLAVE_NAME')) {
            throw new \Exception('SLAVE_NAME environment variable not set');
        }
        if ( !getenv('SLAVE_URL')) {
            throw new \Exception('SLAVE_URL environment variable not set');
        }

        $this->workersNeeded = 0;
        $this->inUsePeak     = 0;

        for($i = 0; $i < $this->numberOfQueues; $i++) {
            $this->queues[$i] = new Queue($this, $i, getenv('SLAVE_NAME'), $this->logger);
        }


        $this->logger->info("Fireping Dispatcher Started.");
        $this->logger->info("Slave name is ".getenv('SLAVE_NAME'));
        $this->logger->info("Slave url is ".getenv('SLAVE_URL'));

        $this->loop = Factory::create();

        $this->loop->addPeriodicTimer(1, function () {
            $toSync    = time() % 120 === 0 ? true : false;

            if ($toSync) {
                $this->logger->info("Starting config sync.");
                $instruction = array('type' => 'config-sync', 'delay_execution' => 0, 'etag' => $this->probeStore->getEtag());
                $this->sendInstruction($instruction);
            }

            foreach($this->queues as $queue) {
                $queue->loop();
            }

            foreach ($this->probeStore->getProbes() as $probe) {
                /* @var $probe ProbeDefinition */

                $ready = time() % $probe->getStep() === 0 ? true : false;

                if ($ready) {

                    $instructionBuilder = $this->getContainer()->get('instruction_builder');
                    $instructions       = $instructionBuilder->create($probe);

                    // Keep track of how many processes are starting.
                    $counter = 0;

                    /* @var $instructions Instruction */
                    foreach ($instructions->getChunks() as $instruction) {
                        $delay                          = intval($counter % ($probe->getStep() / $probe->getSamples()));
                        $counter                        += 1;
                        $instruction['delay_execution'] = $delay;
                        $instruction['guid']            = $this->generateRandomString(25);
                        $this->sendInstruction($instruction, null, $probe->getStep());
                    }
                }
            }

            while ((count($this->availableWorkers) + $this->workersNeeded) < $this->minimumAvailableWorkers) {
                $this->workersNeeded += 1;
            }

            while ($this->workersNeeded != 0) {
                if (count($this->processes) >= $this->maximumWorkers) {
                    $this->logger->critical("Cannot create " . $this->workersNeeded . " more workers, hard limit (maximum-workers=" . $this->maximumWorkers . ") reached.");
                    break;
                } else {
                    if (count($this->processes) >= $this->highWorkersThreshold) {
                        $this->logger->alert("Nearing the upper worker " . $this->highWorkersThreshold . " threshold, investigate high workload or tweak settings!");
                    }
                    $this->startWorker();
                    $this->workersNeeded -= 1;
                }
            }

        });

        // Get worker responses
        $this->loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $pid => $process) {
                try {
                    if ($process) {
                        if (!in_array($pid, $this->inUseWorkers)) {
                            $process->checkTimeout();
                        } else {
                            if (isset($this->startTimes[$pid], $this->expectedRuntime[$pid])) {
                                $actualRuntime   = microtime(true) - $this->startTimes[$pid];
                                $expectedRuntime = $this->expectedRuntime[$pid] + ($this->expectedRuntime[$pid] * 0.25);
                                if ($actualRuntime > $expectedRuntime) {
                                    $this->logger->info("Worker $pid has exceeded the expected runtime, terminating.");
                                    $process->checkTimeout();
                                }
                            }
                        }
                        $process->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $exception) {
                    $this->logger->info("Worker $pid timed out, restarting.");
                    $this->cleanup($pid);
                }
            }
        });

        if ($this->maxRuntime > 0) {
            $this->logger->info("Running for ".$this->maxRuntime." seconds");
            $this->loop->addTimer($this->maxRuntime, function() use ($output) {
                $output->writeln("Max runtime reached");
                $this->loop->stop();
            });
        }

        $this->logger->info("Starting " . $input->getOption('workers') . " workers.");
        for ($w = 0; $w < $input->getOption('workers'); $w++) {
            $worker    = $this->startWorker();
            $workerPid = $worker->getPid();
            $this->logger->info("Worker[$workerPid] started.", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));
            sleep(2);
        }

        $this->loop->run();
    }

    public function sendInstruction(array $instruction, $pid = null, $expectedRuntime = 60)
    {
        if ($pid === null) {
            try {
                $worker = $this->getWorker();
                $pid    = $worker->getPid();
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                return;
            }
        }

        if (json_encode($instruction) === false) {
            $this->logger->critical("Could not send encode the instruction for worker $pid to json.");
            return;
        }

        $json_instruction = json_encode($instruction);

        $input = $this->getInput($pid);
        $input->write($json_instruction);

        $this->logger->info("COMMUNICATION_FLOW: Master sent " . $instruction['type'] . " instruction to worker $pid.");

        $this->startTimes[$pid]      = microtime(true);
        $this->expectedRuntime[$pid] = $expectedRuntime;
    }

    public function getWorker()
    {
        if (count($this->availableWorkers) > 0) {
            if (count($this->availableWorkers) < $this->minimumAvailableWorkers) {
                $this->logger->warning("We do not have the minimum amount of workers available, one will be created.");
                $this->workersNeeded += 1;
            }
            return $this->getWorkerInternal();
        } else {
            $this->workersNeeded += 1;
            throw new \Exception("A worker was requested but none were available.");
        }
    }

    private function getWorkerInternal()
    {
        $pid = array_shift($this->availableWorkers);
        array_push($this->inUseWorkers, $pid);
        $process = $this->processes[$pid];

        if (count($this->inUseWorkers) > $this->inUsePeak) {
            $this->inUsePeak = count($this->inUseWorkers);
        }

        $this->logger->info("Marking worker $pid as in-use.");

        return $process;
    }

    /**
     * Get or create a new InputStream for a given $id.
     *
     * @param int $pid
     * @return mixed
     */
    private function getInput($pid)
    {
        if (!isset($this->processes[$pid])) {
            throw new \Exception("Process for PID=$pid not found.");
        }

        if (!isset($this->inputs[$pid])) {
            throw new \Exception("Input for PID=$pid not found.");
        }

        return $this->inputs[$pid];
    }

    function generateRandomString($length = 10)
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function startWorker()
    {
        $this->logger->info("Starting new worker.");

        $executable  = $this->kernel->getRootDir() . '/../bin/console';
        $environment = $this->kernel->getEnvironment();
        $process     = new Process("exec php $executable app:probe:worker --env=$environment");
        $input       = new InputStream();

        $process->setInput($input);
        $process->setTimeout(1500);
        $process->setIdleTimeout(300);

        $process->start(function ($type, $data) use ($process) {

            $pid = $process->getPid();

            if (isset($this->rcv_buffers[$pid])) {
                $this->rcv_buffers[$pid] .= $data;
            } else {
                $this->rcv_buffers[$pid] = "";
            }

            if (json_decode($this->rcv_buffers[$pid], true)) {
                $this->handleResponse($type, $this->rcv_buffers[$pid]);

                $this->releaseWorker($pid);
            }
        });

        $pid = $process->getPid();
        $this->logger->info("Started Process/$pid");

        $this->processes[$pid]   = $process;
        $this->inputs[$pid]      = $input;
        $this->rcv_buffers[$pid] = "";

        array_push($this->availableWorkers, $pid);

        return $process;
    }

    private function handleResponse($type, $data)
    {
        $response = json_decode($data, true);

        if (!$response) {
            $this->logger->warning("COMMUNICATION_FLOW: Response from worker could not be decoded to JSON.");
            return;
        }

        if (!isset(
            $response['type'],
            $response['status'],
            $response['body']['timestamp'],
            $response['body']['contents'],
            $response['debug'])
        ) {
            $this->logger->error("COMMUNICATION_FLOW: Response ... was missing keys.");
        }

        $type      = $response['type'];
        $status    = $response['status'];
        $timestamp = $response['body']['timestamp'];
        $contents  = $response['body']['contents'];
        $debug     = $response['debug'];
        $pid       = $debug['pid'];
        $runtime   = $debug['runtime'];

        $this->logger->info("COMMUNICATION_FLOW: Master received $type response from worker $pid with a runtime of $runtime.");

        if (!in_array($pid, array_keys($this->processes))) {
            $this->logger->critical("Received a response from an unknown process...");
        }

        switch ($type) {
            case 'exception':
                $this->logger->alert("Response ($status) from worker $pid returned an exception: ".$contents);
                break;

            case 'probe':
                if ($status === 200) {

                    $cleaned = array();

                    foreach ($contents as $id => $content) {
                        if (!isset($content['type'], $content['timestamp'], $content['targets'])) {
                            // TODO: Good warning
                            $this->logger->warning("Response ($status) from worker $pid is missing either a type, timestamp or targets key.");
                        } else {
                            $cleaned[$id] = $content;
                        }
                    }

                    $this->logger->info("Enqueueing the response from worker $pid.");

                    $items = $this->expandProbeResult($cleaned);
                    foreach($items as $key => $item) {
                        $queue = $this->queues[$key%$this->numberOfQueues];
                        $queue->enqueue($item);
                    }

                } else {
                    $this->logger->error("Response ($status) from worker $pid unexpected.");
                }
                break;

            case 'post-result':

                $found = false;
                foreach($this->queues as $queue) {
                    if ($queue->getWorker() == $pid) {
                        $queue->result($status);
                        $this->rcv_buffers[$queue->getWorker()] = "";
                        $found = true;
                    }
                }
                if (!$found) {
                    $this->logger->warning("Could not find the queue for worker $pid");
                }

                break;

            case 'config-sync':
                if ($status === 200) {
                    $etag = $response['headers']['etag'];
                    $this->probeStore->updateConfig($contents, $etag);
                    $this->logger->info("Response ($status) from worker $pid config applied");
                } else {
                    $this->logger->info("Response ($status) from worker $pid received");
                }
                break;

            default:
                $this->logger->error("Response ($status) from worker $pid type $type is not supported by the response handler.");
        }
    }

    private function releaseWorker($pid)
    {
        $this->rcv_buffers[$pid] = "";

        foreach ($this->inUseWorkers as $index => $inUsePid) {
            if (intval($pid) === intval($inUsePid)) {
                unset($this->inUseWorkers[$index]);
            }
        }

        foreach ($this->availableWorkers as $index => $availablePid) {
            if (intval($pid) === intval($availablePid)) {
                $this->logger->warning("Worker $pid was apparently available when asked to be released, investigate!");
                unset($this->availableWorkers[$index]);
            }
        }

        unset($this->startTimes[$pid]);
        unset($this->expectedRuntime[$pid]);

        $this->logger->info("Marking worker $pid as available.");
        array_push($this->availableWorkers, $pid);
    }

    /**
     * Clean up tracking, inputs, processes and receive buffers.
     *
     * @param int $pid
     */
    private function cleanup($pid)
    {
        if (in_array($pid, array_keys($this->trackingGuids))) {
            $this->logger->info("Process [$pid] cleanup started but no data received yet...");
        }

        if (isset($this->processes[$pid])) {
            $this->processes[$pid]->stop(3, SIGINT);
            $this->processes[$pid] = null;
            unset($this->processes[$pid]);
        }

        if (isset($this->inputs[$pid])) {
            $this->inputs[$pid] = null;
            unset($this->inputs[$pid]);
        }

        if (isset($this->rcv_buffers[$pid])) {
            $this->rcv_buffers[$pid] = null;
            unset($this->rcv_buffers[$pid]);
        }

        if (($key = array_search($pid, $this->availableWorkers)) !== false) {
            unset($this->availableWorkers[$key]);
        }

        if (($key = array_search($pid, $this->inUseWorkers)) !== false) {
            unset($this->inUseWorkers[$key]);
        }

        if (isset($this->startTimes[$pid])) {
            $this->startTimes[$pid] = null;
            unset($this->startTimes[$pid]);
        }

        if (isset($this->expectedRuntime[$pid])) {
            $this->expectedRuntime[$pid] = null;
            unset($this->expectedRuntime[$pid]);
        }
    }

    private function expandProbeResult($result)
    {
        $items = array();
        foreach($result as $probeId => $probe) {
            foreach ($probe['targets'] as $key => $target) {
                $items[$key] = array(
                    $probeId => array(
                        'type' => $probe['type'],
                        'timestamp' => $probe['timestamp'],
                        'targets' => array(
                            $key => $target
                        )
                    )
                );
            }
        }

        return $items;
    }
}