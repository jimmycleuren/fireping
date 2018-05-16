<?php

namespace App\DependencyInjection;

use App\Command\ProbeDispatcherCommand;
use Psr\Log\LoggerInterface;

class Queue
{
    private $queue;
    private $lock;
    private $slaveName;
    private $current = null;
    private $worker;
    private $logger;
    private $id;
    private $dispatcher;

    public function __construct(ProbeDispatcherCommand $dispatcher, int $id, string $slaveName, LoggerInterface $logger)
    {
        $this->id = $id;
        $this->logger = $logger;
        $this->slaveName = $slaveName;
        $this->dispatcher = $dispatcher;
        $this->queue = new \SplQueue();
    }

    public function enqueue($data)
    {
        $this->logger->info("Enqueueing in queue $this->id, items in queue: ".$this->queue->count());
        $this->queue->enqueue($data);
    }

    public function loop()
    {
        if (!$this->lock) {
            if (!$this->queue->isEmpty()) {
                try {
                    if (!$this->worker) {
                        $this->reservePoster();
                    }
                    $this->lock = true;
                    $this->current = $this->queue->shift();

                    $instruction = array(
                        'type' => 'post-result',
                        'delay_execution' => 0,
                        'client' => 'eight_points_guzzle.client.api_fireping',
                        'method' => 'POST',
                        'endpoint' => "/api/slaves/" . $this->slaveName . "/result",
                        'headers' => ['Content-Type' => 'application/json'],
                        'body' => $this->current,
                    );

                    $this->dispatcher->sendInstruction($instruction, $this->worker);
                } catch (\Exception $e) {
                    $this->lock = false;
                    $this->logger->warning($e->getMessage());
                }
            }
        }
    }

    public function result($status)
    {
        if ($status === 200) {
            $this->logger->info("Response ($status) from worker $this->worker saved.");
            $this->current = null;
        } elseif ($status === 409) {
            $this->logger->info("Response ($status) from worker .$this->worker discarded.");
            $this->current = null;
        } else {
            $this->logger->info("Response ($status) from worker $this->worker problem - retrying later.");
            $this->retryPost();
        }

        $this->worker = null;
        $this->lock = false;
        $this->logger->info("Queue $this->id items remain: " . $this->queue->count() . ".");
    }

    private function reservePoster()
    {
        try {
            $worker       = $this->dispatcher->getWorker();
            $workerPid    = $worker->getPid();
            $this->worker = $workerPid;
            $this->logger->info("Worker $workerPid reserved to post data for queue ".$this->id);
        } catch (\Exception $e) {
            $this->logger->critical("Could not reserve a worker to post data for queue ".$this->id);
        }
    }

    private function retryPost()
    {
        if (isset($this->current)) {
            $this->logger->info("Retrying " . json_encode($this->queueElement) . " at a later date.");
            $this->queue->unshift($this->current);
            $this->current = null;
        }
    }

    public function getWorker()
    {
        return $this->worker;
    }
}