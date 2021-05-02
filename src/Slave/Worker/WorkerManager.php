<?php

namespace App\Slave\Worker;

use App\Slave\Configuration;
use App\Slave\Exception\WorkerTimedOutException;
use App\Slave\Probe;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class WorkerManager
{
    public const DEVICES_PER_WORKER = 250;

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
            $this->logger->warning('Increasing initial workers to ' . $this->getWorkerBaseline());
            $startWorkers = $this->getWorkerBaseline();
        }

        $this->logger->info("Starting $startWorkers initial workers.");

        for ($w = 0; $w < $startWorkers; ++$w) {
            $this->startWorker();
            sleep(1);
        }
    }

    private function getWorkerBaseline()
    {
        return $this->numberOfQueues + ($this->numberOfProbeProcesses * 2) + 1;
    }

    /**
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
                'worker' => count($this->workers),
            ]
        );

        return $worker;
    }

    public function getNumberOfProbeProcesses()
    {
        return $this->numberOfProbeProcesses;
    }

    public function setNumberOfProbeProcesses(Configuration $configuration)
    {
        $this->numberOfProbeProcesses = (int) \array_reduce($configuration->getProbes(), function ($carry, Probe $probe) {
            return $carry + ceil($probe->getDeviceCount() / self::DEVICES_PER_WORKER);
        }, 0);
    }

    public function getInUseWorkerTypes()
    {
        return $this->inUseWorkerTypes;
    }

    public function getTotalWorkers()
    {
        return count($this->workers);
    }

    public function getAvailableWorkers()
    {
        return count($this->availableWorkers);
    }

    public function getWorker(string $type): Worker
    {
        if (!isset($this->inUseWorkerTypes[$type])) {
            $this->inUseWorkerTypes[$type] = 0;
        }

        if (count($this->availableWorkers) > 0) {
            $worker = array_shift($this->availableWorkers);
            $this->inUseWorkers[] = $worker;

            $this->logger->info("Marking worker $worker as in-use.");

            $worker->setType($type);
            ++$this->inUseWorkerTypes[$type];

            foreach ($this->inUseWorkerTypes as $type => $value) {
                $this->logger->info("$value workers with type $type");
            }

            return $worker;
        }

        throw new \RuntimeException('A worker was requested but none were available.');
    }

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
        --$this->inUseWorkerTypes[$worker->getType()];
        $worker->setType(null);

        foreach ($this->inUseWorkerTypes as $type => $value) {
            $this->logger->info("$value workers with type $type");
        }
    }

    public function loop()
    {
        foreach ($this->workers as $worker) {
            try {
                $worker->loop();
            } catch (ProcessTimedOutException $exception) {
                $this->logger->info("Process $worker timed out", [
                    'available' => count($this->availableWorkers),
                    'inuse' => count($this->inUseWorkers),
                    'worker' => count($this->workers),
                ]);
                $this->cleanup($worker);
            } catch (WorkerTimedOutException $exception) {
                $this->logger->warning("Worker $worker timed out after " . $exception->getTimeout() . ' seconds when running ' . $worker->getType(), [
                    'available' => count($this->availableWorkers),
                    'inuse' => count($this->inUseWorkers),
                    'worker' => count($this->workers),
                ]);
                $this->cleanup($worker);
            }
        }

        //check if we have enough workers available and start 1 if needed
        if (count($this->workers) < $this->getWorkerBaseline()) {
            if (count($this->workers) < $this->maximumWorkers) {
                $this->logger->info('Not enough workers available, starting 1');
                $this->startWorker();
            } else {
                $this->logger->error('Maximum amount of workers (' . $this->maximumWorkers . ') reached');
            }
        }
    }

    /**
     * Clean up tracking, inputs, processes and receive buffers.
     */
    private function cleanup(Worker $worker): void
    {
        $worker->stop();

        if (false !== ($key = array_search($worker, $this->availableWorkers, true))) {
            unset($this->availableWorkers[$key]);
        }

        if (false !== ($key = array_search($worker, $this->inUseWorkers, true))) {
            unset($this->inUseWorkers[$key]);
        }

        if (false !== ($key = array_search($worker, $this->workers, true))) {
            unset($this->workers[$key]);
        }

        if ($worker->getType()) {
            --$this->inUseWorkerTypes[$worker->getType()];
        }
    }
}
