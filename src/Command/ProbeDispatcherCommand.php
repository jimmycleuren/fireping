<?php

declare(strict_types=1);

namespace App\Command;

use App\DependencyInjection\Queue;
use App\DependencyInjection\SlaveConfiguration;
use App\DependencyInjection\StatsManager;
use App\DependencyInjection\WorkerManager;
use App\Instruction\Instruction;
use App\ShellCommand\GetConfigHttpWorkerCommand;
use App\ShellCommand\PostStatsHttpWorkerCommand;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * The number of workers that will be created at most.
     *
     * @var int
     */
    protected $workerLimit;

    /**
     * Used to write to log files.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Holds the configuration of our Fireping Slave.
     *
     * @var SlaveConfiguration
     */
    protected $configuration;

    /**
     * How long the ProbeDispatcher can run for, in seconds. You can specify 0 to
     * indicate an infinitely running process.
     *
     * @var int
     */
    protected $maxRuntime;

    /**
     * The LoopInterface that runs our process.
     *
     * @var LoopInterface
     */
    protected $loop;

    private $workerManager;

    private $statsManager;

    private $devicesPerWorker = 250;

    private $randomFactor = 0;

    public function __construct(LoggerInterface $logger, WorkerManager $workerManager, StatsManager $statsManager)
    {
        $this->logger = $logger;
        $this->workerManager = $workerManager;
        $this->statsManager = $statsManager;
        $this->configuration = new SlaveConfiguration();
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
                'max-runtime',
                'runtime',
                InputOption::VALUE_REQUIRED,
                'The amount of seconds the dispatcher can run for',
                0
            );
    }

    /**
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     */
    private function setUp(InputInterface $input)
    {
        $this->maxRuntime = (int) $input->getOption('max-runtime');
        $this->randomFactor = random_int(0, 119);

        foreach (['SLAVE_NAME', 'SLAVE_URL'] as $item) {
            if (!isset($_ENV[$item])) {
                throw new \RuntimeException("$item environment variable not set.");
            }
        }

        $this->workerManager->initialize(
            intval($input->getOption('workers')),
            intval($input->getOption('maximum-workers')),
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
        $this->logger->info('Random factor is '.$this->randomFactor);

        $this->loop = Factory::create();

        $this->loop->addPeriodicTimer(1, function () {
            if (time() % 120 === $this->randomFactor) {
                $instruction = [
                    'type' => GetConfigHttpWorkerCommand::class,
                    'delay_execution' => 0,
                    'etag' => $this->configuration->getEtag(),
                ];
                $this->sendInstruction($instruction);
            }

            if (time() % 60 === (int) floor($this->randomFactor / 2)) {
                $instruction = [
                    'type' => PostStatsHttpWorkerCommand::class,
                    'delay_execution' => 0,
                    'body' => $this->statsManager->getStats(),
                ];
                $this->sendInstruction($instruction, 30);
            }

            foreach ($this->queues as $queue) {
                $queue->loop();
            }

            foreach ($this->configuration->getProbes() as $probe) {
                $ready = 0 === time() % $probe->getStep();

                if ($ready) {
                    $instructions = new Instruction($probe, $this->devicesPerWorker);

                    // Keep track of how many processes are starting.
                    $counter = 0;

                    foreach ($instructions->getChunks() as $instruction) {
                        $delay = $counter % $probe->getSampleRate();
                        ++$counter;
                        $instruction['delay_execution'] = $delay;
                        $instruction['guid'] = sha1(random_bytes(25));
                        $this->sendInstruction(
                            $instruction,
                            $probe->getStep()
                        );
                    }
                }
            }

            $this->statsManager->addWorkerStats(
                $this->workerManager->getTotalWorkers(),
                $this->workerManager->getAvailableWorkers(),
                $this->workerManager->getInUseWorkerTypes()
            );
        });

        $this->loop->addPeriodicTimer(0.1, function () {
            $this->workerManager->loop();
        });

        if ($this->maxRuntime > 0) {
            $this->logger->info('Running for '.$this->maxRuntime.' seconds');
            $this->loop->addTimer(
                $this->maxRuntime,
                function () use ($output) {
                    $output->writeln('Max runtime reached');
                    $this->loop->stop();
                }
            );
        }

        $this->loop->run();

        return 0;
    }

    /**
     * @param int $expectedRuntime
     *
     * @throws Exception
     */
    public function sendInstruction(array $instruction, $expectedRuntime = null): void
    {
        $expectedRuntime = $expectedRuntime ?? 60;
        try {
            $worker = $this->workerManager->getWorker($instruction['type']);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());

            return;
        }

        $json = json_encode($instruction);
        if (false === $json) {
            $this->logger->critical('Failed to encode instruction: '.json_last_error_msg());

            return;
        }

        $this->logger->debug(sprintf('Sending worker=%s instruction=%s', $worker, $json));
        $worker->send($json, $expectedRuntime, function ($type, $response) {
            $this->handleResponse($type, $response);
        });

        $this->logger->info('COMMUNICATION_FLOW: Master sent '.$instruction['type']." instruction to worker $worker.");
    }

    /**
     * @param string $channel
     * @param string $data
     */
    private function handleResponse($channel, $data): void
    {
        $response = json_decode($data, true);

        if (!$response) {
            $this->logger->warning('COMMUNICATION_FLOW: Response from worker could not be decoded to JSON.');

            return;
        }

        if (!isset(
            $response['type'],
            $response['status'],
            $response['body']['timestamp'],
            $response['body']['contents'],
            $response['debug']
        )
        ) {
            $this->logger->error('COMMUNICATION_FLOW: Response ... was missing keys.');
        }

        $type = $response['type'];
        $status = $response['status'];
        //$timestamp = $response['body']['timestamp'];
        /** @var array $contents */
        $contents = $response['body']['contents'];
        $debug = $response['debug'];
        $pid = $debug['pid'];
        $runtime = $debug['runtime'];

        $this->logger->info("COMMUNICATION_FLOW: Master received $type response from worker $pid with a runtime of $runtime.");

        switch ($type) {
            case 'exception':
                $this->logger->alert("Response ($status) from worker $pid returned an exception: ".print_r($contents, true));
                break;

            case 'probe':
                if (200 === $status) {
                    $cleaned = [];

                    foreach ($contents as $id => $content) {
                        if (!isset($content['type'], $content['timestamp'], $content['targets'])) {
                            // TODO: Good warning
                            $this->logger->warning("Response ($status) from worker $pid is missing either a type, timestamp or targets key.");
                        } else {
                            $cleaned[$id] = $content;
                        }
                    }

                    $this->logger->info("Enqueueing the response from worker $pid.");

                    $items = $this->expandProbeResult($cleaned);
                    foreach ($items as $key => $item) {
                        $queue = $this->queues[$key % $this->numberOfQueues];
                        $queue->enqueue($item);
                    }
                } else {
                    $this->logger->error("Response ($status) from worker $pid unexpected.");
                }
                break;

            case GetConfigHttpWorkerCommand::class:
                if (200 === $status) {
                    $etag = $response['headers']['etag'];
                    $this->configuration->updateConfig($contents, $etag);
                    $this->logger->info("Response ($status) from worker $pid config applied (".$this->configuration->getAllProbesDeviceCount().' devices)');

                    $count = 0;
                    foreach ($this->configuration->getProbes() as $probe) {
                        $count += ceil($this->configuration->getProbeDeviceCount($probe->getId()) / $this->devicesPerWorker);
                    }
                    $this->workerManager->setNumberOfProbeProcesses(intval($count));
                } else {
                    $this->logger->info("Response ($status) from worker $pid received");
                }
                break;

            case PostStatsHttpWorkerCommand::class:
                if (200 === $status) {
                    $this->logger->info('Stats successfully submitted');
                } else {
                    $this->logger->info("Response ($status) from worker $pid received");
                }
                break;

            default:
                $this->logger->error(
                    "Response ($status) from worker $pid type $type is not supported by the response handler."
                );
        }
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
