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

class WorkerManager
{
    /**
     * Holds the Application Kernel
     *
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * An array of process ids of workers that are currently idle.
     *
     * @var int[]
     */
    protected $availableWorkers = [];

    private $workers = [];
    /**
     * An array of process ids of workers that are currently performing a task.
     *
     * @var int[]
     */
    protected $inUseWorkers = [];

    protected $inUseWorkerTypes = [];

    /**
     * The amount of workers that need to be created during the next cycle
     *
     * @var int
     */
    protected $workersNeeded;

    /**
     * The minimum amount of workers that should be idle at all times.
     *
     * @var int
     */
    protected $minimumIdleWorkers;

    /**
     * At most this many workers should ever be created.
     *
     * @var int
     */
    protected $maximumWorkers;

    private $numberOfQueues;


    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public function initialize(int $startWorkers, int $maximumWorkers, int $minimumIdleWorkers, int $numberOfQueues)
    {
        $this->maximumWorkers = $maximumWorkers;
        $this->minimumIdleWorkers = $minimumIdleWorkers;
        $this->numberOfQueues = $numberOfQueues;

        if ($this->minimumIdleWorkers < $this->getWorkerBaseline()) {
            $this->logger->warning("Increasing initial workers to ".$this->getWorkerBaseline());
            $startWorkers = $this->getWorkerBaseline();
        }

        $this->logger->info("Starting $startWorkers initial workers.");

        for ($w = 0; $w < $startWorkers; $w++) {
            $this->startWorker();
            sleep(1);
        }
    }

    private function getWorkerBaseline()
    {
        return $this->numberOfQueues + 1;
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
    }
}