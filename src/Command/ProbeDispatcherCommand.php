<?php
declare(strict_types=1);

namespace App\Command;

use App\DependencyInjection\SlaveConfiguration;
use App\DependencyInjection\Queue;
use App\DependencyInjection\WorkerManager;
use App\Instruction\Instruction;
use App\Probe\GetConfiguration;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProbeDispatcherCommand extends Command
{
    public const MAX_QUEUES = 10;
    public const MAX_DEVICES_PER_WORKER = 250;
    /**
     * @var Queue[]
     */
    protected $queues;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var SlaveConfiguration
     */
    protected $configuration;
    private $workerManager;
    private $randomFactor = 0;

    public function __construct(LoggerInterface $logger, WorkerManager $workerManager)
    {
        $this->logger = $logger;
        $this->workerManager = $workerManager;
        $this->configuration = new SlaveConfiguration();
        parent::__construct();
    }

    /**
     * Configure our command
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
            ->addOption('workers', 'w', InputOption::VALUE_REQUIRED, 'Deprecated', 5)
            ->addOption('maximum-workers', 'max', InputOption::VALUE_REQUIRED, 'Deprecated', 200)
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
    private function setUp()
    {
        $this->randomFactor = random_int(0, 119);

        if (isset($_['SLAVE_NAME'], $_ENV['SLAVE_URL']) === false) {
            throw new \RuntimeException('SLAVE_NAME or SLAVE_URL environment variable not set.');
        }

        $this->workerManager->initialize(self::MAX_QUEUES);

        for ($i = 0; $i < self::MAX_QUEUES; $i++) {
            $this->queues[$i] = new Queue($this->workerManager, $this->logger);
        }
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setUp();

        $maxRuntime = $input->getOption('max-runtime');
        if (is_string($maxRuntime) === false) {
            $maxRuntime = 0;
        }
        $maxRuntime = (int) $maxRuntime;

        $this->logger->info('Fireping Dispatcher Started.');
        $this->logger->info(sprintf('Slave name=%s url=%s randomness=%s', $_ENV['SLAVE_NAME'], $_ENV['SLAVE_URL'], $this->randomFactor));

        $loop = Factory::create();

        $loop->addPeriodicTimer(1, function () {
            if (time() % 120 === $this->randomFactor) {
                $instruction = [
                    'type' => GetConfiguration::class,
                    'delay_execution' => 0,
                    'etag' => $this->configuration->getEtag()
                ];
                $this->sendInstruction($instruction);
            }

            foreach ($this->queues as $queue) {
                $queue->loop();
            }

            foreach ($this->configuration->getProbes() as $probe) {
                $ready = time() % $probe->getStep() === 0;

                if ($ready) {
                    $instructions = new Instruction($probe, self::MAX_DEVICES_PER_WORKER);

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
        });

        $loop->addPeriodicTimer(0.1, function () {
            $this->workerManager->loop();
        });

        if ($maxRuntime > 0) {
            $this->logger->info("Running for $maxRuntime seconds");
            $loop->addTimer($maxRuntime, function () use ($output, $loop) {
                $output->writeln('Max runtime reached');
                $loop->stop();
            }
            );
        }

        $loop->run();
    }

    /**
     * @param array $instruction
     * @param int $expectedRuntime
     *
     * @throws Exception
     */
    public function sendInstruction(array $instruction, int $expectedRuntime = 60): void
    {
        try {
            $worker = $this->workerManager->getWorker($instruction['type']);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            return;
        }

        $json = json_encode($instruction);
        if ($json === false) {
            $this->logger->critical('Failed to encode instruction: ' . json_last_error_msg());
            return;
        }

        $this->logger->debug(sprintf('Sending worker=%s instruction=%s', $worker, $json));
        $worker->send($json, $expectedRuntime, function ($type, $response) {
            $this->handleResponse($type, $response);
        });

        $this->logger->info('COMMUNICATION_FLOW: Master sent ' . $instruction['type'] . " instruction to worker $worker.");
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
                $this->logger->alert("Response ($status) from worker $pid returned an exception: " . print_r($contents, true));
                break;

            case 'probe':
                if ($status === 200) {
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
                        $queue = $this->queues[$key % self::MAX_QUEUES];
                        $queue->enqueue($item);
                    }

                } else {
                    $this->logger->error("Response ($status) from worker $pid unexpected.");
                }
                break;

            case GetConfiguration::class:
                if ($status === 200) {
                    $etag = $response['headers']['etag'];
                    $this->configuration->updateConfig($contents, $etag);
                    $this->logger->info("Response ($status) from worker $pid config applied (" . $this->configuration->getAllProbesDeviceCount() . " devices)");

                    $count = 0;
                    foreach ($this->configuration->getProbes() as $probe) {
                        $count += ceil($this->configuration->getProbeDeviceCount($probe->getId()) / self::MAX_DEVICES_PER_WORKER);
                    }
                    $this->workerManager->setNumberOfProbeProcesses(intval($count));
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

    /**
     * @param array $result
     *
     * @return array
     */
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
                            $key => $target
                        ]
                    ]
                ];
            }
        }

        return $items;
    }
}
