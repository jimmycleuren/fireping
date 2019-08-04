<?php
declare(strict_types=1);

namespace App\DependencyInjection;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;

class WorkerManager
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Worker[]
     */
    private $idleWorkers = [];
    /**
     * @var Worker[]
     */
    private $workers = [];
    /**
     * @var Worker[]
     */
    private $runningWorkers = [];
    /**
     * @var string[]
     */
    private $inUseWorkerTypes = [];
    /**
     * @var int
     */
    private $maximumWorkers;
    private $numberOfQueues = 0;
    private $numberOfProbeProcesses = 0;


    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public function initialize(int $maximumWorkers, int $numberOfQueues)
    {
        $this->maximumWorkers = $maximumWorkers;
        $this->numberOfQueues = $numberOfQueues;

        $workers = $this->getWorkerBaseline();

        $this->logger->info("Starting $workers initial workers.");

        for ($w = 0; $w < $workers; $w++) {
            $this->startWorker();
            sleep(1);
        }
    }

    public function setNumberOfProbeProcesses(int $numberOfProbeProcesses)
    {
        $this->numberOfProbeProcesses = $numberOfProbeProcesses;
    }

    private function getWorkerBaseline()
    {
        return $this->numberOfQueues + ($this->numberOfProbeProcesses * 2) + 1;
    }

    public function getWorker(string $type) : Worker
    {
        if (!isset($this->inUseWorkerTypes[$type])) {
            $this->inUseWorkerTypes[$type] = 0;
        }

        if (count($this->idleWorkers) > 0) {

            $worker = array_shift($this->idleWorkers);
            $this->runningWorkers[] = $worker;

            $this->logger->info("Marking worker $worker as in-use.");

            $worker->setType($type);
            $this->inUseWorkerTypes[$type]++;

            foreach($this->inUseWorkerTypes as $type => $value) {
                $this->logger->info("$value workers with type $type");
            }
            return $worker;
        }

        throw new \RuntimeException('A worker was requested but none were available.');
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     */
    private function startWorker(): Worker
    {
        $worker = new Worker($this, $this->kernel, $this->logger, 1500, 300);
        $worker->start();

        $this->idleWorkers[] = $worker;
        $this->workers[] = $worker;

        $this->logger->info(
            "Worker $worker started.",
            [
                'idle' => count($this->idleWorkers),
                'running' => count($this->runningWorkers),
                'total' => count($this->workers)
            ]
        );

        return $worker;
    }

    /**
     * @param Worker $worker
     */
    public function release(Worker $worker): void
    {
        foreach ($this->runningWorkers as $index => $inUseWorker) {
            if ($worker === $inUseWorker) {
                unset($this->runningWorkers[$index]);
            }
        }

        foreach ($this->idleWorkers as $index => $availableWorker) {
            if ($worker === $availableWorker) {
                $this->logger->warning("Worker $worker was apparently available when asked to be released, investigate!");
                unset($this->idleWorkers[$index]);
            }
        }

        $this->logger->info("Marking worker $worker as available.");
        $this->idleWorkers[] = $worker;
        $this->inUseWorkerTypes[$worker->getType()]--;
        $worker->setType(null);

        foreach($this->inUseWorkerTypes as $type => $value) {
            $this->logger->info("$value workers with type $type");
        }
    }

    /**
     * Clean up tracking, inputs, processes and receive buffers.
     *
     * @param Worker $worker
     */
    private function cleanup(Worker $worker): void
    {
        $worker->stop();

        if (($key = array_search($worker, $this->idleWorkers, true)) !== false) {
            unset($this->idleWorkers[$key]);
        }

        if (($key = array_search($worker, $this->runningWorkers, true)) !== false) {
            unset($this->runningWorkers[$key]);
        }

        if (($key = array_search($worker, $this->workers, true)) !== false) {
            unset($this->workers[$key]);
        }
    }

    public function loop()
    {
        foreach ($this->workers as $worker) {
            try {
                $worker->loop();
            } catch (ProcessTimedOutException $exception) {
                $this->logger->info("Worker $worker timed out", [
                    'available' => count($this->idleWorkers),
                    'inuse' => count($this->runningWorkers),
                    'worker' => count($this->workers)
                ]);
                $this->cleanup($worker);
            }
        }

        //check if we have enough workers available and start 1 if needed
        if (count($this->workers) < $this->getWorkerBaseline()) {
            if(count($this->workers) < $this->maximumWorkers) {
                $this->logger->info("Not enough workers available, starting 1");
                $this->startWorker();
            } else {
                $this->logger->error("Maximum amount of workers (".$this->maximumWorkers.") reached");
            }
        }
    }
}