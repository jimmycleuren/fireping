<?php

namespace AppBundle\Command;

use AppBundle\DependencyInjection\ProbeStore;
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

    /** @var KernelInterface */
    protected $kernel;

    /** @var LoggerInterface */
    protected $logger;

    /** @var \SplQueue */
    protected $instructionQueue;

    /** @var ProbeStore */
    protected $probeStore;

    protected $trackingGuids = array();

    protected $rcv_buffers = array();

    protected $availableWorkers = array();

    protected $inUseWorkers = array();

    protected function configure()
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
            ->addOption(
                'workers',
                'w',
                InputOption::VALUE_REQUIRED,
                'Specifies the amount of workers to use.',
                50
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->kernel = $this->getContainer()->get('kernel');
        $this->logger = $this->getContainer()->get('logger');

        $this->queue            = new \SplQueue();

        $this->logger->info("Fireping Dispatcher Started.");

        $loop = Factory::create();

        $this->probeStore = $this->getContainer()->get('probe_store');

        // Configuration sync
        $loop->addPeriodicTimer(60, function () {
            $this->logger->info("ProbeStore Sync Started");
            $this->probeStore->sync($this->logger);
        });

        // Main event loop
        $loop->addPeriodicTimer(1, function () {
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
                        try {
                            $worker = $this->getWorker();

                            // Cycle between 0-3 seconds delay before executing command (fping/traceroute) on the list of targets
                            $delay   = intval($counter % ($probe->getStep() / $probe->getSamples()));
                            $counter += 1;

                            $workerPid = $worker->getPid();
                            $this->logger->info("Worker[$workerPid] started ok; delay_execution=$delay");

                            $input = $this->getInput($workerPid);

                            $instruction['delay_execution'] = $delay;

                            $instruction['guid'] = $this->generateRandomString(25);
                            foreach ($instruction['targets'] as $target) {
                                if ($target['id'] == 216) {
                                    $this->trackingGuids[$workerPid] = $instruction['guid'];
                                    $this->logger->info("Instruction sent to worker.", array(
                                        'debug_mark' => 'STUPIDBUG',
                                        'worker_pid' => $workerPid,
                                        'guid' => $instruction['guid'],
                                        'device_id' => 216,
                                    ));
                                }
                            }

                            $instruction = json_encode($instruction);

                            $this->logger->info("Sending instruction to worker $workerPid", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));
                            //$this->logger->info("Sending instruction to pid/$workerPid: $instruction", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));

                            $input->write($instruction);
                        } catch (\Exception $exception) {
                            $this->logger->warning("There are no available workers, this slave is probably getting too much work.");
                            continue;
                        }
                    }
                }
            }
        });

        // Queue processor
        $loop->addPeriodicTimer(60, function () {
            $remaining = $this->queue->count();
            $this->logger->info("Queue currently has $remaining items left to be processed.");

            if (!$this->queueLock) {
                $this->queueLock = true;
                while (!$this->queue->isEmpty()) {
                    $node = $this->queue->shift();
                    try {
                        $this->postResults($node);
                    } catch (TransferException $exception) {
                        $code = $exception->getCode();
                        if ($code === 409) {
                            // Conflict detected, discard the message.
                            $this->logger->warning("Master tells us we are sending old data, discarding it.");
                        } else {
                            $this->logger->warning("Master indicated a problem on their end, retrying later.");
                            $this->queue->unshift($node);
                            $this->queueLock = false;
                            break;
                        }
                    }
                }
                $this->queueLock = false;
            } else {
                $this->logger->warning("Queue is currently locked.");
            }
        });

        // Other Queue Processor
//        $loop->addPeriodicTimer(1 * 30, function () {
//            $this->logger->info("Processing queues.");
//            $this->queueHandler->processQueues();
//        });

        // Get worker responses
        $loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $pid => $process) {
                try {
                    if ($process) {
                        $process->checkTimeout();
                        $process->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $exception) {
                    if (in_array($pid, array_keys($this->trackingGuids))) {
                        $this->logger->info("Process [$pid] timeout.", array('debug_mark' => 'STUPIDBUG'));
                    }
                    $this->cleanup($pid);
                    $this->logger->info("Worker $pid timed out; starting new one...");
                    $this->startWorker();
                }
            }
        });

        $this->logger->info("Loading configuration for the first time.");
        $this->probeStore->sync($this->logger);

        $this->logger->info("Starting " . $input->getOption('workers') . " workers.");
        for ($w = 0; $w < $input->getOption('workers'); $w++) {
            $worker    = $this->startWorker();
            $workerPid = $worker->getPid();
            $this->logger->info("Worker[$workerPid] started.", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));
            sleep(2);
        }

        $loop->run();
    }

    private function getWorker()
    {
        $this->logger->info("Dispatcher requests a worker.", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));
        if (count($this->availableWorkers) > 0) {

            $pid = array_shift($this->availableWorkers);
            array_push($this->inUseWorkers, $pid);
            $process = $this->processes[$pid];

            return $process;

        } else {
            $this->logger->critical("Dispatcher needed a worker but we ran out.  Resource issue!");
            throw new \Exception("Dispatcher needed a worker but we ran out.  Resource issue!");
        }
    }

    /**
     * Get or create a new InputStream for a given $id.
     *
     * @param $pid
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

    /**
     * Posts the formatted results from any probe to the master.
     *
     * @param array $results
     */
    private function postResults(array $results)
    {
        /** @var \GuzzleHttp\Client $client */
        $client = $this->getContainer()->get('guzzle.client.api_fireping');

        $id       = $this->getContainer()->getParameter('slave.name');
        $endpoint = "/api/slaves/$id/result";

        $data = json_encode($results);
        try {
            $this->logger->info("Posting $endpoint with data: $data");
            $response   = $client->post($endpoint, [
                'body' => json_encode($results),
            ]);
            $statusCode = $response->getStatusCode();
            $msg        = $response->getReasonPhrase();
            $body       = $response->getBody();
            $this->logger->info("$msg Response ($statusCode) from $endpoint: $body");
        } catch (TransferException $exception) {
            $message = $exception->getMessage();
            $this->logger->error("Exception (message: $message) was thrown while posting data to $endpoint.");
            throw $exception; // TODO: This probably just shouldn't throw any exceptions and instead let the timer handle it.
        }
    }

    /**
     * Dereferences old processes and inputs.
     *
     * @param $id
     */
    private function cleanup($id)
    {
        if (in_array($id, array_keys($this->trackingGuids))) {
            $this->logger->info("Process [$id] cleanup started but no data received yet...", array('debug_mark' => 'STUPIDBUG'));
        }

        if (isset($this->processes[$id])) {
            $this->processes[$id]->stop(3, SIGINT);
            $this->processes[$id] = null;
            unset($this->processes[$id]);
        }

        if (isset($this->inputs[$id])) {
            $this->inputs[$id] = null;
            unset($this->inputs[$id]);
        }

        if (isset($this->rcv_buffers[$id])) {
            $this->rcv_buffers[$id] = null;
            unset($this->rcv_buffers[$id]);
        }
    }

    private function startWorker()
    {
        $this->logger->info("Starting new worker...");

        $executable  = $this->kernel->getRootDir() . '/../bin/console';
        $environment = $this->kernel->getEnvironment();
        $process     = new Process("exec php $executable app:probe:worker --env=$environment -vvv");

        $input = new InputStream();
        $process->setInput($input);
        $process->setTimeout(3600);
        $process->setIdleTimeout(1200);

        $process->start(function ($type, $data) use ($process) {
            $pid = $process->getPid();
            $this->logger->info("Worker $pid sends data: $data");

            if (isset($this->rcv_buffers[$pid])) {
                $this->rcv_buffers[$pid] .= $data;
            } else {
                $this->rcv_buffers[$pid] = "";
            }

            if (json_decode($this->rcv_buffers[$pid], true)) {
                $this->logger->info("Buffer of worker $pid is readable, processing...");
                $this->handleResponse($type, $this->rcv_buffers[$pid]);
                $this->logger->info("Releasing worker $pid");
                $this->releaseWorker($pid);
            }

            if (strlen($this->rcv_buffers[$pid]) > 300) {
                $this->logger->info("WorkerData $pid looks like this after 300 characters: " .$this->rcv_buffers[$pid]);
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
        $decoded = json_decode($data, true);

        if (in_array($decoded['debug']['request_data']['guid'], $this->trackingGuids)) {
            $this->logger->info('Received data from worker', array(
                'debug_mark' => 'STUPIDBUG',
                'guid' => $decoded['debug']['request_data']['guid'],
                'worker_pid' => $decoded['debug']['pid'],
                'req_dev' => $decoded['debug']['req_dev'],
                'ret_dev' => $decoded['debug']['ret_dev'],
            ));
            if (isset($this->trackingGuids[$decoded['debug']['pid']])) {
                $this->logger->info("Stopped tracking " . $decoded['debug']['request_data']['guid'],
                    array(
                        'worker_pid' => $decoded['debug']['pid'],
                    ));
                unset($this->trackingGuids[$decoded['debug']['pid']]);
            }
            if (in_array(216, array_keys($decoded['body'][1]['targets']))) {
                $this->logger->info('Received expected data from Worker.', array(
                    'debug_mark' => 'STUPIDBUG',
                    'worker_pid' => $decoded['debug']['pid'],
                    'guid' => $decoded['debug']['request_data']['guid'],
                    'device_id' => 216,
                ));
            } else {
                $this->logger->info('Received unexpected data from Worker - now cry.', array(
                    'debug_mark' => 'STUPIDBUG',
                    'worker_pid' => $decoded['debug']['pid'],
                    'guid' => $decoded['debug']['request_data']['guid'],
                    'device_id' => 216,
                ));
            }
        }

        if (!$decoded) {
            $this->logger->warning("$data could not be decoded to JSON.", array('debug_mark', 'STUPIDBUG'));
            return;
        }

        if (!isset($decoded['status'], $decoded['message'], $decoded['body'])) {
            $this->logger->warning('One more or required keys {status,message,body} are missing from worker response: ' . json_encode($decoded));
            return;
        }

        if ($decoded['status'] == 500) {
            //TODO: This is temporary!!
            $this->logger->error($data);
        } else {
            // TODO: Handle different status codes.  Right now, we assume that only data is sent.
            // TODO: Should also handle client and server errors.
            $cleaned = array();
            foreach ($decoded['body'] as $id => $message) {
                if (!isset($message['type'], $message['timestamp'], $message['targets'])) {
                    $this->logger->warning('One or more required keys {type, timestamp, targets} are missing from the response body: ' . json_encode($decoded));
                    continue; // Do not attempt to post incomplete results.
                }
                $cleaned[$id] = $message;
            }
//        if ($decoded['status'] == Message::MESSAGE_OK) {
//            $this->logger->info("Adding message to data queue.");
//            $this->queueHandler->addMessage('data', new Message(
//                Message::MESSAGE_OK,
//                'Message OK',
//                $cleaned
//            ));
//        } else {
//            $this->logger->info("Adding message to exceptions queue.");
//            $this->queueHandler->addMessage('exceptions', new Message(
//                Message::SERVER_ERROR,
//                'An error...',
//                $decoded
//            ));
//        }
            $this->queue->enqueue($cleaned);
        }
    }

    private function releaseWorker($pid)
    {
        $this->logger->info("Starting release flow for worker $pid");
        $this->rcv_buffers[$pid] = "";

        foreach ($this->inUseWorkers as $index => $inUsePid) {
            if (intval($pid) === intval($inUsePid)) {
                unset($this->inUseWorkers[$index]);
                $this->logger->info("Released worker $pid");
            }
        }

        foreach ($this->availableWorkers as $index => $availablePid) {
            if (intval($pid) === intval($availablePid)) {
                $this->logger->warning("Worker $pid was apparently available when asked to be released, investigate!");
                unset($this->availableWorkers[$index]);
            }
        }

        $this->logger->info("Marking worker $pid as available.");
        array_push($this->availableWorkers, $pid);
    }

    protected function executeOld(InputInterface $input, OutputInterface $output)
    {
        $this->kernel       = $this->getContainer()->get('kernel');
        $this->logger       = $this->getContainer()->get('logger');
        $id                 = $this->getContainer()->getParameter('slave.name');
        $poster             = new EchoPoster("https://smokeping-dev.cegeka.be/api/slaves/$id/result");
        $this->queueHandler = new MessageQueueHandler($poster);
        $this->queueHandler->addQueue(new MessageQueue('data'));
        $this->queueHandler->addQueue(new MessageQueue('exceptions'));

        $this->workerLimit = $input->getOption('workers-limit');

        $this->queue = new \SplQueue();
        $pid         = getmypid();
        $now         = date('l jS \of F Y h:i:s A');

        $this->logger->info("Slave started at $now");

        $loop = Factory::create();

        $probeStore = $this->getContainer()->get('probe_store');

        $loop->addPeriodicTimer(60, function () use ($pid, $probeStore) {
            $this->logger->info("ProbeStore Sync Started");
            $probeStore->sync($this->logger);
        });

        $loop->addPeriodicTimer(1, function () use ($probeStore) {
            foreach ($probeStore->getProbes() as $probe) {
                /* @var $probe ProbeDefinition */
                $now       = time();
                $remainder = $now % $probe->getStep();

                if (!$remainder) {

                    $instructionBuilder = $this->getContainer()->get('instruction_builder');
                    $instructions       = $instructionBuilder->create($probe);

                    // Keep track of how many processes are starting.
                    $counter = 0;

                    $step    = $probe->getStep();
                    $samples = $probe->getSamples();
                    $delay   = 0;

                    /* @var $instructions Instruction */
                    foreach ($instructions->getChunks() as $instruction) {
                        try {
                            $worker = $this->getWorker();
                            // Cycle between 0-3 seconds delay before executing command (fping/traceroute) on the list of targets
                            $delay   = intval($counter % ($step / $samples));
                            $counter += 1;
                        } catch (\Exception $exception) {
                            $this->logger->warning("Workers limit has been reached.");
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
                        $input     = $this->getInput($workerPid);
                        $this->logger->info("Worker/$workerPid is asked to delay execution by $delay seconds.");
                        $instruction['delay_execution'] = $delay;
                        $instruction                    = json_encode($instruction);
                        $this->logger->info("Sending instruction to worker $workerPid");
                        //$this->logger->info("Sending instruction to pid/$workerPid: $instruction");
                        $input->write($instruction);
                    }
                }
            }
        });

        $loop->addPeriodicTimer(1 * 60, function () {
            $x = $this->queue->count();
            $this->logger->info("Queue currently has $x items left to be processed.");
            if (!$this->queueLock) {
                $this->queueLock = true;
                while (!$this->queue->isEmpty()) {
                    $node = $this->queue->shift();
                    try {
                        $this->postResults($node);
                    } catch (TransferException $exception) {
                        $code = $exception->getCode();
                        if ($code === 409) {
                            // Conflict detected, discard the message.
                            $this->logger->warning("Master tells us we are sending old data, discarding it.");
                        } else {
                            $this->logger->warning("Master indicated a problem on their end, retrying later.");
                            $this->queue->unshift($node);
                            $this->queueLock = false;
                            break;
                        }
                    }
                }
                $this->queueLock = false;
            } else {
                $this->logger->warning("Queue is currently locked.");
            }
        });

        $loop->addPeriodicTimer(1 * 30, function () {
            $this->logger->info("Processing queues.");
            $this->queueHandler->processQueues();
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

        $this->logger->info("Initializing ProbeStore.");
        $probeStore->sync($this->logger);

        $loop->run();
    }

    /**
     * Get a new Worker process.
     *
     * @return Process
     */
    private function getWorkerOld()
    {
//        if (count($this->processes) > $this->workerLimit) {
//            throw new \Exception("Worker limit reached.");
//        }

        $executable  = $this->kernel->getRootDir() . '/../bin/console';
        $environment = $this->kernel->getEnvironment();
        $process     = new Process("exec php $executable app:probe:worker --env=$environment -vvv");
        $input       = new InputStream();
        $process->setInput($input);
        $process->setTimeout(180);
        $process->setIdleTimeout(120);
        $process->start(function ($type, $data) use ($process) {
            $pid = $process->getPid();
            $this->logger->info("Worker[$pid] returns data: $data");
            $this->handleResponse($type, $data);
            $this->logger->info("Killing Process/$pid");
            $this->cleanup($pid);
        });
        $pid = $process->getPid();
        $this->logger->info("Started Process/$pid");

        $this->processes[$pid] = $process;
        $this->inputs[$pid]    = $input;

        return $process;
    }
}