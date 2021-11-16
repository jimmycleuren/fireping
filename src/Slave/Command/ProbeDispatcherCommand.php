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
use React\EventLoop\Loop;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function time;
use const JSON_THROW_ON_ERROR;

final class ProbeDispatcherCommand extends Command
{
    private const DEFAULT_NUMBER_OF_QUEUES = 10;
    private const DEVICES_PER_WORKER = 250;

    /**
     * @var Queue[]
     */
    private array $queues = [];

    private LoggerInterface $logger;
    private Configuration $configuration;

    /**
     * How long the ProbeDispatcher can run for, in seconds. You can specify 0 to
     * indicate an infinitely running process.
     */
    private int $maxRuntime = 0;
    private WorkerManager $workerManager;
    private StatsManager $statsManager;

    public function __construct(LoggerInterface $logger, WorkerManager $workerManager, StatsManager $statsManager)
    {
        $this->logger = $logger;
        $this->workerManager = $workerManager;
        $this->statsManager = $statsManager;
        $this->configuration = new Configuration();
        parent::__construct();
    }

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

    private function setUp(InputInterface $input)
    {
        $this->maxRuntime = (int) $input->getOption('max-runtime');

        foreach (['SLAVE_NAME', 'SLAVE_URL'] as $item) {
            if (!isset($_ENV[$item])) {
                throw new \RuntimeException("$item environment variable not set.");
            }
        }

        $this->workerManager->initialize(
            (int) $input->getOption('workers'),
            (int) $input->getOption('maximum-workers'),
            self::DEFAULT_NUMBER_OF_QUEUES
        );

        for ($i = 0; $i < self::DEFAULT_NUMBER_OF_QUEUES; ++$i) {
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setUp($input);

        $this->logger->info('Fireping Dispatcher Started.');
        $this->logger->info('Slave name is '.$_ENV['SLAVE_NAME']);
        $this->logger->info('Slave url is '.$_ENV['SLAVE_URL']);

        Loop::addPeriodicTimer(120, function () {
            $this->sendInstruction([
                'type' => FetchConfiguration::class,
                'delay_execution' => 0,
                'etag' => $this->configuration->getEtag(),
                'timestamp' => time(),
            ]);
        });

        Loop::addPeriodicTimer(60, function () {
            $this->sendInstruction([
                'type' => PublishStatistics::class,
                'delay_execution' => 0,
                'body' => $this->statsManager->getStats(),
                'timestamp' => time(),
            ]);
        });

        Loop::addPeriodicTimer(1, function () {
            $this->statsManager->addWorkerStats(
                $this->workerManager->getTotalWorkers(),
                $this->workerManager->getAvailableWorkers(),
                $this->workerManager->getInUseWorkerTypes()
            );
        });

        Loop::addPeriodicTimer(0.1, function () {
            $this->workerManager->loop();
        });

        Loop::addPeriodicTimer(1, function () {
            foreach ($this->queues as $queue) {
                $queue->loop();
            }
        });

        Loop::addPeriodicTimer(1, function () {
            $now = time();

            foreach ($this->configuration->getProbes() as $probe) {
                $ready = 0 === $now % $probe->getStep();

                if ($ready) {
                    $instructions = new Instruction($probe, self::DEVICES_PER_WORKER);

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

        if ($this->maxRuntime > 0) {
            Loop::addTimer($this->maxRuntime, function () use ($output) {
                $output->writeln('Max runtime reached');
                Loop::stop();
            });
        }

        Loop::run();

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function sendInstruction(array $instruction, int $expectedRuntime = 60): void
    {
        try {
            $this->logger->info(sprintf('selecting worker for type %s', $instruction['type']));
            $worker = $this->workerManager->getWorker($instruction['type']);
            $this->logger->info(sprintf('worker %s selected', (string) $worker));
        } catch (Exception $e) {
            $this->logger->error('could not select worker: ' . $e->getMessage());

            return;
        }

        $json = json_encode($instruction, JSON_THROW_ON_ERROR);
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
                    $queue = $this->queues[$key % self::DEFAULT_NUMBER_OF_QUEUES];
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
                        $count += ceil($this->configuration->getProbeDeviceCount($probe->getId()) / self::DEVICES_PER_WORKER);
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
