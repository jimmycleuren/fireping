<?php

declare(strict_types=1);

namespace App\Slave\Command;

use App\Common\Process\SymfonyProcessFactory;
use App\Common\Version\GitVersionReader;
use App\Slave\Configuration;
use App\Slave\Instruction;
use App\Slave\Task\FetchConfiguration;
use App\Slave\Task\PublishStatistics;
use App\Slave\Worker\Queue;
use App\Slave\Worker\StatsManager;
use App\Slave\Worker\WorkerManager;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class ProbeDispatcherCommand.
 */
class ProbeDispatcherCommand extends Command
{
    /**
     * The number of queues that will be created by the ProbeDispatcher.
     *
     * @var int
     */
    protected $numberOfQueues = 10;

    /**
     * A collection of queues available to the ProbeDispatcher.
     *
     * @var Queue[]
     */
    protected $queues;

    /**
     * Used to write to log files.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Holds the configuration of our Fireping Slave.
     *
     * @var Configuration
     */
    protected $configuration;

    private $workerManager;

    private $statsManager;

    private $devicesPerWorker = 250;

    private $executionOffset = 0;

    public function __construct(LoggerInterface $logger, WorkerManager $workerManager, StatsManager $statsManager)
    {
        $this->logger = $logger;
        $this->workerManager = $workerManager;
        $this->statsManager = $statsManager;
        $this->configuration = new Configuration();
        parent::__construct();
    }

    /**
     * Configure our command.
     *
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
            ->addOption(
                'workers',
                'w',
                InputOption::VALUE_REQUIRED,
                'Specifies the amount of workers to start out with.',
                "5"
            )
            ->addOption(
                'maximum-workers',
                'max',
                InputOption::VALUE_REQUIRED,
                'Specifies the maximum amount of workers that can ever be created.',
                "200"
            )
            ->addOption(
                'max-runtime',
                'runtime',
                InputOption::VALUE_REQUIRED,
                'The amount of seconds the dispatcher can run for',
                "0"
            );
    }

    /**
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     */
    private function setUp(InputInterface $input)
    {
        $this->executionOffset = random_int(0, 119);

        foreach (['SLAVE_NAME', 'SLAVE_URL'] as $item) {
            if (!isset($_ENV[$item])) {
                throw new \RuntimeException("$item environment variable not set.");
            }
        }

        $this->workerManager->initialize(
            (int) $input->getOption('workers'),
            (int) $input->getOption('maximum-workers'),
            $this->numberOfQueues
        );

        for ($i = 0; $i < $this->numberOfQueues; ++$i) {
            $this->queues[$i] = new Queue(
                $this->workerManager,
                $this->statsManager,
                $i,
                $_ENV['SLAVE_NAME'],
                $this->logger
            );
        }

        $this->statsManager->setVersion((new GitVersionReader($this->logger, new SymfonyProcessFactory()))->version());
    }

    private function addWorkerStatsTimer(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(1, function () {
            $this->statsManager->addWorkerStats(
                $this->workerManager->getTotalWorkers(),
                $this->workerManager->getAvailableWorkers(),
                $this->workerManager->getInUseWorkerTypes()
            );
        });
    }

    /**
     * @return int|void|null
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setUp($input);

        $this->logger->info('Fireping Dispatcher Started.');
        $this->logger->info('Slave name is '.$_ENV['SLAVE_NAME']);
        $this->logger->info('Slave url is '.$_ENV['SLAVE_URL']);
        $this->logger->info('Random factor is '.$this->executionOffset);

        $loop = Factory::create();

        $this->addProbeHandleTimer($loop);
        $this->addFetchConfigurationTimer($loop);
        $this->addPublishStatsTimer($loop);
        $this->addQueueLoopTimer($loop);
        $this->addWorkerManagerLoopTimer($loop);
        $this->addWorkerStatsTimer($loop);
        $this->addEarlyTimeoutTimer((int) $input->getOption('max-runtime'), $loop);

        $loop->run();

        return 0;
    }

    private function addProbeHandleTimer(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(1, function () {
            $now = time();

            foreach ($this->configuration->getProbes() as $probe) {
                $ready = 0 === $now % $probe->getStep();

                if ($ready) {
                    $instructions = new Instruction($probe, $this->devicesPerWorker);

                    // Keep track of how many processes are starting.
                    $counter = 0;

                    foreach ($instructions->getChunks() as $instruction) {
                        $delay = $counter % $probe->getSampleRate();
                        ++$counter;
                        $instruction['delay_execution'] = $delay;
                        $instruction['timestamp'] = $now;
                        $this->sendInstruction($instruction, $probe->getStep());
                    }
                }
            }
        });
    }

    private function addFetchConfigurationTimer(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(120, function () {
            $this->sendInstruction([
                'type' => FetchConfiguration::class,
                'delay_execution' => 0,
                'etag' => $this->configuration->getEtag(),
                'timestamp' => time(),
            ]);
        });
    }

    private function addPublishStatsTimer(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(60, function () {
            $this->sendInstruction([
                'type' => PublishStatistics::class,
                'delay_execution' => 0,
                'body' => $this->statsManager->getStats(),
                'timestamp' => time(),
            ], 30);
        });
    }

    private function addQueueLoopTimer(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(1, function () {
            foreach ($this->queues as $queue) {
                $queue->loop();
            }
        });
    }

    private function addWorkerManagerLoopTimer(LoopInterface $loop)
    {
        $loop->addPeriodicTimer(0.1, function () {
            $this->workerManager->loop();
        });
    }

    private function addEarlyTimeoutTimer(int $timeout, LoopInterface $loop)
    {
        if ($timeout === 0) {
            return;
        }

        $loop->addTimer($timeout, function () use ($loop) {
            $this->logger->info('Dispatcher timed out');
            $loop->stop();
        });
    }

    /**
     * @throws Exception
     */
    public function sendInstruction(array $instruction, int $expectedRuntime = 60): void
    {
        try {
            $this->logger->info(sprintf('selecting worker for type %s', $instruction['type']));
            $worker = $this->workerManager->getWorker($instruction['type']);
            $this->logger->info(sprintf('worker %s selected', (string) $worker));
        } catch (Exception $e) {
            $this->logger->error('could not select worker: ' . $e->getMessage());

            return;
        }

        $json = json_encode($instruction);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('failed to encode instruction: ' . json_last_error_msg());

            return;
        }

        $startAt = microtime(true);

        $this->logger->info(sprintf('sending instruction to worker %s (%d bytes)', (string) $worker, strlen($json)));
        $this->logger->debug(sprintf('worker %s instruction: %s', (string) $worker, $json));

        $worker->send($json, $expectedRuntime, function ($type, $response) {
            if (Process::OUT === $type) {
                $this->handleResponse($response);
            }

            if (Process::ERR === $type) {
                fwrite(STDERR, $response);
            }
        });

        $this->logger->info(sprintf('sent instruction to worker %s (took %s seconds)', (string) $worker, microtime(true) - $startAt));
    }

    private function handleResponse(string $data): void
    {
        $startAt = microtime(true);
        $bytes = strlen($data);
        $response = json_decode($data, true);

        if (!$response) {
            $this->logger->error('malformed json received from worker');

            return;
        }

        if (!isset($response['pid'], $response['type'], $response['status'], $response['headers'], $response['contents'])) {
            $this->logger->error('incomplete response received from worker');

            return;
        }

        $pid = $response['pid'];
        $type = $response['type'];
        $status = $response['status'];
        /** @var array|string $contents */
        $contents = $response['contents'];

        $this->logger->info("$type response ($bytes bytes) received from worker $pid");

        switch ($type) {
            case 'probe':
                $this->logger->info("enqueueing the response from worker $pid.");

                $items = $this->expandProbeResult($contents);
                foreach ($items as $key => $item) {
                    $queue = $this->queues[$key % $this->numberOfQueues];
                    $queue->enqueue($item);
                }
                break;

            case FetchConfiguration::class:
                $this->logger->info('started handling new configuration response');
                if (200 === $status) {
                    $this->logger->info('applying new configuration');
                    $etag = $response['headers']['etag'];
                    $this->configuration->updateConfig($contents, $etag);
                    $this->logger->info(sprintf('new configuration applied (took %.2f seconds)', microtime(true) - $startAt));
                    $this->logger->info(sprintf('new configuration has %d probes and %d devices', count($this->configuration->getProbes()), $this->configuration->getAllProbesDeviceCount()));

                    $count = 0;
                    foreach ($this->configuration->getProbes() as $probe) {
                        $count += ceil($this->configuration->getProbeDeviceCount($probe->getId()) / $this->devicesPerWorker);
                    }
                    $this->workerManager->setNumberOfProbeProcesses(intval($count));
                } else {
                    $this->logger->warning("configuration response ($status) from worker $pid received");
                }
                break;

            default:
                $this->logger->warning("unexpected response of type $type");
        }

        $runtime = microtime(true) - $startAt;
        $this->logger->info(sprintf('finished handling response from worker %d (took %.2f seconds)', $pid, $runtime));
    }

    private function expandProbeResult(array $result): array
    {
        $items = [];
        foreach ($result as $probeId => $probe) {
            /** @var array $targets */
            $targets = $probe['targets'];
            foreach ($targets as $key => $target) {
                $items[$key] = [
                    $probeId => [
                        'type' => $probe['type'],
                        'timestamp' => $probe['timestamp'],
                        'targets' => [
                            $key => $target,
                        ],
                    ],
                ];
            }
        }

        return $items;
    }
}
