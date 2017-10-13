<?php

namespace AppBundle\Command;

use AppBundle\DependencyInjection\ProbeStore;
use AppBundle\Instruction\Instruction;

use AppBundle\Probe\ProbeDefinition;

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
    /** @var array */
    protected $processes = array();

    /** @var array */
    protected $inputs = array();

    /** @var \SplQueue */
    protected $queue;

    /** @var boolean */
    protected $queueLock;

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

    /**
     * This contains the process id of the worker process that is responsible for posting data to the master.
     * @var $poster int
     */
    protected $poster;

    protected $queueElement;

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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->kernel     = $this->getContainer()->get('kernel');
        $this->logger     = $this->getContainer()->get('logger');
        $this->probeStore = $this->getContainer()->get('probe_store');

        $this->initWorkers             = $input->getOption('workers');
        $this->minimumAvailableWorkers = $input->getOption('minimum-available-workers');
        $this->maximumWorkers = $input->getOption('maximum-workers');
        $this->highWorkersThreshold    = $input->getOption('high-workers-threshold');

        if ($this->highWorkersThreshold >= $this->maximumWorkers) {
            throw new \Exception("High workers threshold value must be less than maximum workers value.");
        }

        $this->workersNeeded = 0;
        $this->inUsePeak = 0;

        $this->queue = new \SplQueue();

        $this->logger->info("Fireping Dispatcher Started.");

        $loop = Factory::create();

        $loop->addPeriodicTimer(1, function () {
            $toSync    = time() % 120 === 0 ? true : false;
            $showDebug = time() % 10 === 0 ? true : false;

            if ($showDebug) {
                $this->logger->info(
                    "Available: " . count($this->availableWorkers) .
                    " In Use: " . count($this->inUseWorkers) .
                    " Processes " . count($this->processes) .
                    " Needed: " . $this->workersNeeded .
                    " In Use (Peak): " . $this->inUsePeak
                );
            }

            if ($toSync) {
                $this->logger->info("Starting config sync.");
                try {
                    $worker    = $this->getWorker();
                    $workerPid = $worker->getPid();
                    $input     = $this->getInput($workerPid);

                    $instruction = array('type' => 'config-sync', 'delay_execution' => 0, 'etag' => $this->probeStore->getEtag());

                    $this->logger->info("COMMUNICATION_FLOW: Master sent config-sync instruction to $workerPid");
                    $input->write(json_encode($instruction));
                } catch (\Exception $exception) {
                    $this->logger->warning("There are no available workers, this slave is probably getting too much work.");
                }
            }

            if (isset($this->poster) && $this->poster != -1) {
                if (!$this->queueLock) {
                    if (!$this->queue->isEmpty()) {
                        $this->queueLock    = true;
                        $name               = $this->getContainer()->getParameter('slave.name');
                        $this->queueElement = $this->queue->shift();
                        $input              = $this->getInput($this->poster);

                        $instruction = array(
                            'type' => 'post-result',
                            'delay_execution' => 0,
                            'client' => 'guzzle.client.api_fireping',
                            'method' => 'POST',
                            'endpoint' => "/api/slaves/$name/result",
                            'headers' => ['Content-Type' => 'application/json'],
                            'body' => $this->queueElement,
                        );

                        $this->logger->info("COMMUNICATION_FLOW: Master sent post-result instruction to " . $this->poster);
                        $input->write(json_encode($instruction));
                    }
                }
            } else {
                $this->reservePoster();
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
                        try {
                            $worker = $this->getWorker();

                            // Cycle between 0-3 seconds delay before executing command (fping/traceroute) on the list of targets
                            $delay   = intval($counter % ($probe->getStep() / $probe->getSamples()));
                            $counter += 1;

                            $workerPid = $worker->getPid();

                            $input = $this->getInput($workerPid);

                            $instruction['delay_execution'] = $delay;

                            $instruction['guid'] = $this->generateRandomString(25);

                            $this->logger->info("COMMUNICATION_FLOW: Master sent probe(" . $instruction['type'] . ") instruction to $workerPid");

                            $instruction = json_encode($instruction);

                            $input->write($instruction);
                        } catch (\Exception $exception) {
                            $this->logger->warning("There are no available workers, this slave is probably getting too much work.");
                            continue;
                        }
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
                }
                else {
                    if (count($this->processes) >= $this->highWorkersThreshold) {
                        $this->logger->alert("Nearing the upper worker " . $this->highWorkersThreshold . " threshold, investigate high workload or tweak settings!");
                    }
                    $this->startWorker();
                    $this->workersNeeded -= 1;
                }
            }

        });

        // Get worker responses
        $loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $pid => $process) {
                try {
                    if ($process) {
                        $process->checkTimeout();
                        $process->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $exception) {
                    $this->logger->info("Worker $pid timed out, restarting.");
                    $this->cleanup($pid);
                    $this->startWorker();
                }
            }
        });

        //$this->logger->info("Loading configuration for the first time.");
        //$this->probeStore->sync($this->logger);

        $this->logger->info("Starting " . $input->getOption('workers') . " workers.");
        for ($w = 0; $w < $input->getOption('workers'); $w++) {
            $worker    = $this->startWorker();
            $workerPid = $worker->getPid();
            $this->logger->info("Worker[$workerPid] started.", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));
            sleep(2);
        }

        $this->reservePoster();

        $loop->run();
    }

    private function getWorker()
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

    private function reservePoster()
    {
        try {
            $worker       = $this->getWorker();
            $workerPid    = $worker->getPid();
            $this->poster = $workerPid;
            $this->logger->info("Worker $workerPid reserved to post data.");
        } catch (\Exception $e) {
            $this->logger->critical("Could not reserve a worker to post data.");
        }
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
        $process->setTimeout(3600);
        $process->setIdleTimeout(1200);

        $process->start(function ($type, $data) use ($process) {

            $pid = $process->getPid();
            //$this->logger->info("processing raw data for pid $pid: $data");

            if (isset($this->rcv_buffers[$pid])) {
                $this->rcv_buffers[$pid] .= $data;
            } else {
                $this->rcv_buffers[$pid] = "";
            }

            if (json_decode($this->rcv_buffers[$pid], true)) {
                $this->handleResponse($type, $this->rcv_buffers[$pid]);

                // TODO: (Note) Posters should be reserved until the queue is empty.
                if ($pid != $this->poster) {
                    $this->releaseWorker($pid);
                } else {
                    $this->logger->info("Should not clean up the poster!", array('pid' => $pid, 'poster' => $this->poster));
                }
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

        switch ($type) {
            case 'exception':
                $this->logger->alert("Response ($status) from worker $pid returned an exception.");
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
                    $this->queue->enqueue($cleaned);
                } else {
                    $this->logger->error("Response ($status) from worker $pid unexpected.");
                }
                break;

            case 'post-result':

                if ($status === 200) {
                    $this->logger->info("Response ($status) from worker $pid for $type saved.");
                } elseif ($status === 409) {
                    $this->logger->info("Response ($status) from worker $pid for $type discarded.");
                } else {
                    $this->logger->info("Response ($status) from worker $pid for $type problem - retrying later.");
                    $this->queue->unshift($this->queueElement);
                    $this->queueElement = null;
                    $this->releasePoster();
                }

                $this->queueLock = false;
                $this->logger->info("COMMUNICATION_FLOW Response ($status) post-result items remain: " . $this->queue->count() . ".");
                $this->rcv_buffers[$this->poster] = "";
                break;

            case 'config-sync':
                $this->logger->info("COMMUNICATION_FLOW: Master received " . $response['type'] . " response from worker " . $response['debug']['pid'] . " with a runtime of " . $response['debug']['runtime']);
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

    private function releasePoster()
    {
        if (isset($this->poster) && $this->poster != -1) {
            $this->releaseWorker($this->poster);
            $this->poster = -1;
        } else {
            $this->logger->warning("Asked to release poster but none were reserved.");
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

        $this->logger->info("Marking worker $pid as available.");
        array_push($this->availableWorkers, $pid);
    }

    /**
     * Clean up tracking, inputs, processes and receive buffers.
     *
     * @param $pid
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

        if ($pid === $this->poster) {
            $this->poster = -1;
        }
    }
}