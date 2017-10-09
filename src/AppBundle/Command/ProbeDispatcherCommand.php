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
                'Specifies the amount of workers to use.',
                50
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->kernel     = $this->getContainer()->get('kernel');
        $this->logger     = $this->getContainer()->get('logger');
        $this->probeStore = $this->getContainer()->get('probe_store');

        $this->queue = new \SplQueue();

        $this->logger->info("Fireping Dispatcher Started.");

        $loop = Factory::create();

        $loop->addPeriodicTimer(1, function () {
            $toSync = time() % 10 === 0 ? true : false;

            if ($toSync) {
                $this->logger->info("Starting config sync.");
                try {
                    $worker    = $this->getWorker();
                    $workerPid = $worker->getPid();
                    $input     = $this->getInput($workerPid);

                    $instruction = array('type' => 'config-sync', 'delay_execution' => 0, 'etag' => $this->probeStore->getEtag());

                    $input->write(json_encode($instruction));
                } catch (\Exception $exception) {
                    $this->logger->warning("There are no available workers, this slave is probably getting too much work.");
                }
            }

            if (isset($this->poster) && $this->poster != -1) {
                $this->logger->info("QueueLock: " . ($this->queueLock === true ? "yes" : "no") . " | QueueItems: " . $this->queue->count() . " | Poster: " . $this->poster);
                if (!$this->queueLock) {
                    if (!$this->queue->isEmpty()) {
                        $this->queueLock = true;
                        $name = $this->getContainer()->getParameter('slave.name');
                        $this->queueElement = $this->queue->shift();
                        $input = $this->getInput($this->poster);

                        $instruction = array(
                            'type' => 'post-result',
                            'delay_execution' => 0,
                            'client' => 'guzzle.client.api_fireping',
                            'method' => 'POST',
                            'endpoint' => "/api/slaves/$name/result",
                            'headers' => ['Content-Type' => 'application/json'],
                            'body' => $this->queueElement,
                        );

                        $input->write(json_encode($instruction));
                    }
                }
            } else {
                $this->reservePoster();
            }

            foreach ($this->probeStore->getProbes() as $probe) {
                /* @var $probe ProbeDefinition */

                $ready  = time() % $probe->getStep() === 0 ? true : false;

                if ($ready) {

                    $instructionBuilder = $this->getContainer()->get('instruction_builder');
                    $instructions       = $instructionBuilder->create($probe);

                    // Keep track of how many processes are starting.
                    $counter = 0;

                    /* @var $instructions Instruction */
                    foreach ($instructions->getChunks() as $instruction) {
                        var_dump($instruction);
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

                            $instruction = json_encode($instruction);

                            //$this->logger->info("Sending instruction to worker $workerPid", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));
                            $this->logger->info("Sending instruction to pid/$workerPid: $instruction", array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));

                            $input->write($instruction);
                        } catch (\Exception $exception) {
                            $this->logger->warning("There are no available workers, this slave is probably getting too much work.");
                            continue;
                        }
                    }
                }
            }
        });

        /*$loop->addPeriodicTimer(60, function () {
            $remaining = $this->queue->count();
            $this->logger->info("Queue remaining $remaining");

            if (!$this->queueLock) {

                if (!$this->queue->isEmpty()) {
                    $this->reservePoster();
                }
                else {
                    $this->logger->info("No results need to be posted.");
                }

                while (!$this->queue->isEmpty()) {
                    try {
                        $worker = $this->getWorker();
                        $this->poster = $worker->getPid();
                        $this->logger->info("Reserving worker: " . $this->poster);
                    } catch (\Exception $e) {
                        $this->logger->critical("No workers available to post results.");
                    }
                }
            }
        });*/

        // Queue processor
        /*$loop->addPeriodicTimer(1200, function () {
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
        });*/

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
                        $this->logger->info("Process [$pid] timeout.");
                    }
                    $this->cleanup($pid);
                    $this->logger->info("Worker $pid timed out; starting new one...");
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

    private function reservePoster()
    {
        try {
            $worker = $this->getWorker();
            $workerPid = $worker->getPid();
            $this->poster = $workerPid;
            $this->logger->info("Worker $workerPid reserved to post data.");
        } catch (\Exception $e) {
            $this->logger->critical("Could not reserve a worker to post data.");
        }
    }

    private function releasePoster()
    {
        if (isset($this->poster) && $this->poster != -1) {
            $this->releaseWorker($this->poster);
            $this->poster = -1;
        }
        else {
            $this->logger->warning("Asked to release poster but none were reserved.");
        }
    }

    private function handleResponse($type, $data)
    {
        $response = json_decode($data, true);

        if (!$response) {
            $this->logger->warning("Response from worker could not be decoded to JSON.");
            return;
        }

        if (!isset(
            $response['type'],
            $response['status'],
            $response['body']['timestamp'],
            $response['body']['contents'],
            $response['debug'])) {
            $this->logger->error("Response ... was missing keys.");
        }

        $type = $response['type'];
        $status = $response['status'];
        $timestamp = $response['body']['timestamp'];
        $contents = $response['body']['contents'];
        $debug = $response['debug'];

        switch ($type)
        {
            case 'exception':
                $this->logger->alert("Response ($status) returned an exception.");
                break;

            case 'probe':
                if ($status === 200) {

                    $cleaned = array();

                    foreach ($contents as $id => $content) {
                        if (!isset($content['type'], $content['timestamp'], $content['targets'])) {
                            // TODO: Good warning
                            $this->logger->warning("Response missing keys...");
                        }
                        else {
                            $cleaned[$id] = $content;
                        }
                    }

                    $this->logger->info("Enqueueing the probe results.");
                    $this->queue->enqueue($cleaned);
                }
                else {
                    $this->logger->error('Response probe ...');
                }
                break;

            case 'post-result':
                if ($status === 200) {
                    $this->logger->critical("Response ($status) " . json_encode($this->queueElement) . " saved.");
                }
                elseif ($status === 409) {
                    $this->logger->critical("Response ($status) " . json_encode($this->queueElement) . " discarded.");
                }
                else {
                    $this->logger->critical("Response ($status) " . json_encode($this->queueElement) . " retrying later.");
                    $this->queue->unshift($this->queueElement);
                    $this->queueElement = null;
                    $this->releasePoster();
                }
                $this->logger->critical("Unlocking Queue");
                $this->queueLock = false;
                $this->logger->info("After Processing Result - QueueLock: " . ($this->queueLock === true ? "yes" : "no") . " | QueueItems: " . $this->queue->count() . " | Poster: " . $this->poster);
                break;

            case 'config-sync':
                if ($status === 200) {
                    $etag = $response['headers']['etag'];
                    $this->probeStore->updateConfig($contents, $etag);
                    $this->logger->info("Response ($status) " . json_encode($contents) . " config applied");
                } else {
                    $this->logger->info("Response ($status) " . json_encode($contents) . " received");
                }
                break;
        }
    }

    private function handleResponseOld($type, $data)
    {
        $decoded = json_decode($data, true);
        $type = $decoded['body']['type'];

        if ($type === 'post_result') {

            $code = $decoded['body']['contents']['code'];

            if ($code === 200) {
                // OK - Results were saved - Proceed with the next one.
                // Send the next instruction to the same worker.
                $this->logger->info("Response ($code) " . $this->queueElement . " saved successfully.");
            }
            elseif ($code === 409) {
                // NOK - Results were not saved because of conflict -> Discard data.
                // Send the next instruction to the same worker.
                $this->logger->warning("Response ($code) " . $this->queueElement . " conflict discarded.");
            }
            else {
                // NOK - Something went wrong, we should retry at a later time.
                // Abort - try again later.
                $this->logger->warning("Response ($code) " . $this->queueElement . " retrying later.");
                $this->queue->unshift($this->queueElement);
                $this->queueElement = null;
                $this->releasePoster();
            }

            $this->queueLock = false;
            return;
        }

        if ($type === 'http') {
            $pid = $decoded['body']['pid'];
            if ($decoded['body']['contents']['code'] === 200) {
                $this->probeStore->updateConfig($decoded['body']['contents']['contents'], $decoded['body']['contents']['etag']);
            } else {
                // TODO: Handle these other types of return traffic... i.e. clear the config.
                $this->logger->info("Data received from HTTP probe: " . $data);
            }
            return;
        }

        if (isset($decoded['debug']) && in_array($decoded['debug']['request_data']['guid'], $this->trackingGuids)) {
            $this->logger->info('Received data from worker');
            if (isset($this->trackingGuids[$decoded['debug']['pid']])) {
                $this->logger->info("Stopped tracking " . $decoded['debug']['request_data']['guid']);
                unset($this->trackingGuids[$decoded['debug']['pid']]);
            }
            if (in_array(216, array_keys($decoded['body'][1]['targets']))) {
                $this->logger->info('Received expected data from Worker.');
            } else {
                $this->logger->info('Received unexpected data from Worker - now cry.');
            }
        }

        if (!$decoded) {
            $this->logger->warning("$data could not be decoded to JSON.");
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

            $this->queue->enqueue($cleaned);
        }
    }

    private function getWorker()
    {
        $this->logger->info("Dispatcher requests a worker.",
            array('available' => count($this->availableWorkers), 'inuse' => count($this->inUseWorkers), 'processes' => count($this->processes)));

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
    }

    private function startWorker()
    {
        $this->logger->info("Starting new worker.");

        $executable  = $this->kernel->getRootDir() . '/../bin/console';
        $environment = $this->kernel->getEnvironment();
        $process     = new Process("exec php $executable app:probe:worker --env=$environment -vvv");
        $input       = new InputStream();

        $process->setInput($input);
        $process->setTimeout(3600);
        $process->setIdleTimeout(1200);

        $process->start(function ($type, $data) use ($process) {

            $pid = $process->getPid();
            $this->logger->info("processing raw data for pid $pid: $data");

            if (isset($this->rcv_buffers[$pid])) {
                $this->rcv_buffers[$pid] .= $data;
            } else {
                $this->rcv_buffers[$pid] = "";
            }

            if (json_decode($this->rcv_buffers[$pid], true)) {
                $this->handleResponse($type, $this->rcv_buffers[$pid]);

                $this->logger->info("Releasing $pid");
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

    private function releaseWorker($pid)
    {
        $this->logger->info("Starting release flow for worker $pid");
        $this->rcv_buffers[$pid] = "";

        var_dump($this->inUseWorkers);

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
}