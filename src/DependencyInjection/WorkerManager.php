<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 4/09/2018
 * Time: 10:27
 */

namespace App\DependencyInjection;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class WorkerManager
{
    protected $kernel;

    private $logger;

    /**
     * An array of process ids of workers that are currently idle.
     *
     * @var Worker[]
     */
    private $availableWorkers = [];

    /**
     * @var Worker[]
     */
    private $workers = [];
    /**
     * An array of process ids of workers that are currently performing a task.
     *
     * @var Worker[]
     */
    private $inUseWorkers = [];

    private $inUseWorkerTypes = [];

    /**
     * The minimum amount of workers that should be idle at all times.
     *
     * @var int
     */
    private $minimumIdleWorkers;

    /**
     * At most this many workers should ever be created.
     *
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

    public function initialize(int $startWorkers, int $maximumWorkers, int $numberOfQueues)
    {
        $this->maximumWorkers = $maximumWorkers;
        $this->numberOfQueues = $numberOfQueues;

        if ($startWorkers < $this->getWorkerBaseline()) {
            $this->logger->warning("Increasing initial workers to ".$this->getWorkerBaseline());
            $startWorkers = $this->getWorkerBaseline();
        }

        $this->logger->info("Starting $startWorkers initial workers.");

        for ($w = 0; $w < $startWorkers; $w++) {
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

        if (count($this->availableWorkers) > 0) {

            $worker = array_shift($this->availableWorkers);
            $this->inUseWorkers[] = $worker;

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
     *
     * @return Worker
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function startWorker(): Worker
    {
        $worker = new Worker($this, $this->kernel, $this->logger, 1500, 300);
        $worker->start();

        $this->availableWorkers[] = $worker;
        $this->workers[] = $worker;

        $this->logger->info(
            "Worker $worker started.",
            [
                'available' => count($this->availableWorkers),
                'inuse' => count($this->inUseWorkers),
                'worker' => count($this->workers)
            ]
        );

        return $worker;
    }

    /**
     * @param Worker $worker
     */
    public function release(Worker $worker): void
    {
        foreach ($this->inUseWorkers as $index => $inUseWorker) {
            if ($worker === $inUseWorker) {
                unset($this->inUseWorkers[$index]);
            }
        }

        foreach ($this->availableWorkers as $index => $availableWorker) {
            if ($worker === $availableWorker) {
                $this->logger->warning("Worker $worker was apparently available when asked to be released, investigate!");
                unset($this->availableWorkers[$index]);
            }
        }

        $this->logger->info("Marking worker $worker as available.");
        $this->availableWorkers[] = $worker;
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

        if (($key = array_search($worker, $this->availableWorkers, true)) !== false) {
            unset($this->availableWorkers[$key]);
        }

        if (($key = array_search($worker, $this->inUseWorkers, true)) !== false) {
            unset($this->inUseWorkers[$key]);
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
                    'available' => count($this->availableWorkers),
                    'inuse' => count($this->inUseWorkers),
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