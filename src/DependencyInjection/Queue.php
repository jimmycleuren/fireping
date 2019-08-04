<?php
declare(strict_types=1);

namespace App\DependencyInjection;

use App\Probe\SendResults;
use Psr\Log\LoggerInterface;

class Queue
{
    private $queue;
    private $lock;
    private $current = null;
    private $worker;
    private $logger;
    private $workerManager;
    private $targetsPerPacket = 50;

    public function __construct(WorkerManager $workerManager, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->workerManager = $workerManager;
        $this->queue = new \SplQueue();
    }

    public function enqueue($data)
    {
        $this->queue->enqueue($data);
    }

    public function loop()
    {
        if (!$this->lock) {
            if (!$this->queue->isEmpty()) {
                try {
                    if (!$this->worker) {
                        $this->reserve();
                        $this->logger->warning("Queue had no worker");
                        return;
                    }

                    $this->lock = true;
                    $this->current = $this->getNextPacket();

                    $instruction = array(
                        'type' => SendResults::class,
                        'delay_execution' => 0,
                        'body' => $this->current,
                    );

                    $this->worker->send(json_encode($instruction), 60, function($type, $response){
                        $this->handleResponse($type, $response);
                    });

                    $this->logger->info('Queue sent ' . $instruction['type'] . " instruction to worker $this->worker.");

                } catch (\Exception $e) {
                    $this->lock = false;
                    $this->logger->warning($e->getMessage()." at ".$e->getFile().":".$e->getLine());
                }
            }
        }
    }

    private function handleResponse($type, $data)
    {
        $response = json_decode($data, true);

        if (!$response) {
            $this->logger->warning('COMMUNICATION_FLOW: Response from worker could not be decoded to JSON.');
            return;
        }

        $status = $response['status'];

        if ($status === 200) {
            $this->logger->info("Response ($status) from worker $this->worker saved.");
            $this->current = null;
        } elseif ($status === 409) {
            $this->logger->info("Response ($status) from worker $this->worker discarded.");
            $this->current = null;
        } else {
            $this->logger->info("Response ($status) from worker $this->worker problem - retrying later.");
            $this->retry();
        }

        $this->lock = false;
        $this->worker = null;
        $this->logger->info("Queue items remain: " . $this->queue->count() . ".");
    }

    private function getNextPacket()
    {
        $first = $this->queue->shift();
        $firstProbeId = array_keys($first)[0];

        $counter = count($first[$firstProbeId]['targets']);
        $stop = false;
        while($counter < $this->targetsPerPacket && !$this->queue->isEmpty() && !$stop) {
            $next = $this->queue->shift();
            $nextProbeId = array_keys($next)[0];

            if($firstProbeId == $nextProbeId && $first[$firstProbeId]['timestamp'] == $next[$nextProbeId]['timestamp'] && $first[$firstProbeId]['type'] == $next[$nextProbeId]['type']) {
                $nextTargetId = array_keys($next[$nextProbeId]['targets'])[0];
                $first[$firstProbeId]['targets'][$nextTargetId] = $next[$nextProbeId]['targets'][$nextTargetId];
            } else {
                $this->queue->unshift($next);
                $stop = true;
            }
            $counter++;
        }

        $this->logger->info("Queue will send $counter items");

        return $first;
    }

    private function reserve()
    {
        try {
            $this->worker = $this->workerManager->getWorker('queue');
            $this->logger->info("Worker $this->worker reserved to post data for queue");
        } catch (\Exception $e) {
            $this->logger->critical("Could not reserve a worker to post data for queue");
        }
    }

    private function retry()
    {
        if (isset($this->current)) {
            $this->logger->info("Retrying " . json_encode($this->current) . " at a later date.");
            $this->queue->unshift($this->current);
            $this->current = null;
        }
    }
}