<?php
declare(strict_types=1);

namespace App\Command;

use App\DependencyInjection\ProbeStore;
use App\DependencyInjection\Queue;
use App\Instruction\Instruction;

use App\Instruction\InstructionBuilder;
use App\Probe\ProbeDefinition;


use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class ProbeDispatcherCommand
 *
 * @package App\Command
 */
class ProbeDispatcherCommand extends ContainerAwareCommand
{
    /**
     * Indexed by process id, the processes used for workers
     *
     * @var Process[]
     */
    protected $processes = [];

    /**
     * Indexed by process id, the input streams uses to write to the process
     *
     * @var InputStream[]
     */
    private $inputStreams = [];

    /**
     * The number of queues that will be created by the ProbeDispatcher
     *
     * @var int
     */
    protected $numberOfQueues = 10;

    /**
     * A collection of queues available to the ProbeDispatcher
     *
     * @var Queue[]
     */
    protected $queues;

    /**
     * The number of workers that will be created at most
     *
     * @var int
     */
    protected $workerLimit;

    /**
     * Holds the Application Kernel
     *
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Used to write to log files
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Holds the configuration of our Fireping Slave
     *
     * @var ProbeStore
     */
    protected $probeStore;

    /**
     * A collection of GUIDs associated with a request tracked over the course of
     * its lifecycle
     *
     * @var string[]
     */
    private $trackingIds = [];

    /**
     * Indexed per process id, the in-memory string buffer used to hold data
     * received from the process.
     *
     * @var string[]
     */
    private $receiveBuffers = [];

    /**
     * An array of process ids of workers that are currently idle.
     *
     * @var int[]
     */
    protected $availableWorkers = [];

    /**
     * An array of process ids of workers that are currently performing a task.
     *
     * @var int[]
     */
    protected $inUseWorkers = [];

    /**
     * The amount of workers that need to be created during the next cycle
     *
     * @var int
     */
    protected $workersNeeded;

    /**
     * The initial amount of workers to create when starting the dispatcher.
     *
     * @var int
     */
    protected $initWorkers;

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

    /**
     * The threshold indicating a high amount of workers is being used, and we
     * should create more.
     *
     * @var
     */
    protected $highWorkersThreshold;

    /**
     * The peak number of in use workers.
     *
     * @var
     */
    protected $inUsePeak;

    /**
     * How long the ProbeDispatcher can run for, in seconds. You can specify 0 to
     * indicate an infinitely running process.
     *
     * @var int
     */
    protected $maxRuntime;

    /**
     * Indexed by process id and stored in micro time, an in-memory storage of
     * when a given worker was started.
     *
     * @var float[]
     */
    protected $startTimes = [];

    /**
     * Indexed by process id and stored in seconds, the amount of time we expect a
     * given worker to run for before forcefully terminating it.
     *
     * @var int[]
     */
    protected $expectedRuntime = [];

    /**
     * The LoopInterface that runs our process.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Service used to create a set of instructions to send to a worker.
     *
     * @var InstructionBuilder
     */
    private $instructionBuilder;

    /**
     * ProbeDispatcherCommand constructor.
     *
     * @param ProbeStore         $probeStore P
     * @param LoggerInterface    $logger     Instance used to log information about
     *                                       the state of our program.
     *
     * @param InstructionBuilder $instructionBuilder
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        ProbeStore $probeStore,
        LoggerInterface $logger,
        InstructionBuilder $instructionBuilder
    ) {
        $this->logger = $logger;
        $this->probeStore = $probeStore;
        $this->instructionBuilder = $instructionBuilder;
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
            ->addOption(
                'workers',
                'w',
                InputOption::VALUE_REQUIRED,
                'Specifies the amount of workers to start out with.',
                50
            )
            ->addOption(
                'minimum-available-workers',
                'min',
                InputOption::VALUE_REQUIRED,
                'Specifies the minimum amount of available workers at all times.',
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
                'high-workers-threshold',
                'high',
                InputOption::VALUE_REQUIRED,
                'Threshold for alerting when a high number of workers is reached.',
                150
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
     * @param InputInterface $input
     *
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     */
    private function setUp(InputInterface $input)
    {
        $this->kernel = $this->getContainer()->get('kernel');

        $this->initWorkers = $input->getOption('workers');
        $this->minimumIdleWorkers = $input->getOption('minimum-available-workers');
        $this->maximumWorkers = $input->getOption('maximum-workers');
        $this->highWorkersThreshold = $input->getOption('high-workers-threshold');
        $this->maxRuntime = $input->getOption('max-runtime');

        if ($this->highWorkersThreshold >= $this->maximumWorkers) {
            $str = 'High workers threshold must be less than maximum workers';
            throw new RuntimeException($str);
        }

        foreach (['SLAVE_NAME', 'SLAVE_URL'] as $item) {
            if (!getenv($item)) {
                throw new \RuntimeException("$item environment variable not set.");
            }
        }

        $this->workersNeeded = 0;
        $this->inUsePeak = 0;

        for ($i = 0; $i < $this->numberOfQueues; $i++) {
            $this->queues[$i] = new Queue(
                $this,
                $i,
                getenv('SLAVE_NAME'),
                $this->logger
            );
        }
    }

    /**
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setUp($input);

        $this->logger->info('Fireping Dispatcher Started.');
        $this->logger->info('Slave name is ' . getenv('SLAVE_NAME'));
        $this->logger->info('Slave url is ' . getenv('SLAVE_URL'));

        $this->loop = Factory::create();

        $this->loop->addPeriodicTimer(
            1,
            function () {
                $toSync = time() % 120 === 0;

                if ($toSync) {
                    $this->logger->info('Starting config sync.');
                    $instruction = [
                        'type' => 'config-sync',
                        'delay_execution' => 0,
                        'etag' => $this->probeStore->getEtag()
                    ];
                    $this->sendInstruction($instruction);
                }

                foreach ($this->queues as $queue) {
                    $queue->loop();
                }

                foreach ($this->probeStore->getProbes() as $probe) {
                    /* @var $probe ProbeDefinition */

                    $ready = time() % $probe->getStep() === 0;

                    if ($ready) {
                        $instructions = $this->instructionBuilder::create(
                            $probe,
                            250
                        );

                        // Keep track of how many processes are starting.
                        $counter = 0;

                        /* @var $instructions Instruction */
                        foreach ($instructions->getChunks() as $instruction) {
                            $delay = (
                                $counter % ($probe->getStep() / $probe->getSamples())
                            );
                            ++$counter;
                            $instruction['delay_execution'] = $delay;
                            $instruction['guid'] = $this->generateRandomString(25);
                            $this->sendInstruction(
                                $instruction,
                                null,
                                $probe->getStep()
                            );
                        }
                    }
                }

                //always have 1 worker more as the minumum needed to avoid warnings
                $this->workersNeeded = max(
                    $this->minimumIdleWorkers - count($this->availableWorkers) - $this->workersNeeded + 1,
                    0
                );

                while ($this->workersNeeded !== 0) {
                    if (count($this->processes) >= $this->maximumWorkers) {
                        $this->logger->critical("Cannot create " . $this->workersNeeded . " more workers, hard limit (maximum-workers=" . $this->maximumWorkers . ") reached.");
                        break;
                    }

                    if (count($this->processes) >= $this->highWorkersThreshold) {
                        $this->logger->alert("Nearing the upper worker " . $this->highWorkersThreshold . " threshold, investigate high workload or tweak settings!");
                    }
                    $this->startWorker();
                    --$this->workersNeeded;
                }
            }
        );

        // Get worker responses
        $this->loop->addPeriodicTimer(
            0.1,
            function () {
                foreach ($this->processes as $pid => $process) {
                    try {
                        if ($process) {
                            if (!\in_array($pid, $this->inUseWorkers, true)) {
                                $process->checkTimeout();
                            } elseif (isset($this->startTimes[$pid], $this->expectedRuntime[$pid])) {
                                $actualRuntime = microtime(true) - $this->startTimes[$pid];
                                $expectedRuntime = $this->expectedRuntime[$pid] * 1.25;
                                if ($actualRuntime > $expectedRuntime) {
                                    $str = "Worker $pid has exceeded the expected runtime, terminating.";
                                    $this->logger->info($str);
                                    $process->checkTimeout();
                                }
                            }
                            $process->getIncrementalOutput();
                        }
                    } catch (ProcessTimedOutException $exception) {
                        $this->logger->info("Worker $pid timed out", [
                            'available' => count($this->availableWorkers),
                            'inuse' => count($this->inUseWorkers),
                            'processes' => count($this->processes)
                        ]);
                        $this->cleanup($pid);
                    }
                }
            }
        );

        if ($this->maxRuntime > 0) {
            $this->logger->info('Running for ' . $this->maxRuntime . ' seconds');
            $this->loop->addTimer(
                $this->maxRuntime,
                function () use ($output) {
                    $output->writeln('Max runtime reached');
                    $this->loop->stop();
                }
            );
        }

        $this->logger->info('Starting ' . $input->getOption('workers') . ' workers.');
        for ($w = 0; $w < $input->getOption('workers'); $w++) {
            $worker = $this->startWorker();
            $workerPid = $worker->getPid();
            $this->logger->info(
                "Worker[$workerPid] started.",
                [
                    'available' => count($this->availableWorkers),
                    'inuse' => count($this->inUseWorkers),
                    'processes' => count($this->processes)
                ]
            );
            sleep(2);
        }

        $this->loop->run();
    }

    /**
     * @param array $instruction
     * @param null  $pid
     * @param int   $expectedRuntime
     *
     * @throws Exception
     */
    public function sendInstruction(
        array $instruction,
        $pid = null,
        $expectedRuntime = null
    ): void {
        $expectedRuntime = $expectedRuntime ?? 60;
        if ($pid === null) {
            try {
                $worker = $this->getWorker();
                $pid = $worker->getPid();
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage());
                return;
            }
        }

        if (json_encode($instruction) === false) {
            $str = "Could not send encode the instruction for worker $pid to json.";
            $this->logger->critical($str);
            return;
        }

        $jsonInstruction = json_encode($instruction);

        $input = $this->getInput($pid);
        $input->write($jsonInstruction);

        $this->logger->info('COMMUNICATION_FLOW: Master sent ' . $instruction['type'] . " instruction to worker $pid.");

        $this->startTimes[$pid] = microtime(true);
        $this->expectedRuntime[$pid] = $expectedRuntime;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getWorker()
    {
        if (count($this->availableWorkers) > 0) {
            if (count($this->availableWorkers) < $this->minimumIdleWorkers) {
                $this->logger->warning('We do not have the minimum amount of workers available, one will be created.');
                ++$this->workersNeeded;
            }
            return $this->getWorkerInternal();
        }

        ++$this->workersNeeded;
        throw new RuntimeException('A worker was requested but none were available.');
    }

    /**
     *
     * @return Process
     */
    private function getWorkerInternal(): Process
    {
        $pid = array_shift($this->availableWorkers);
        $this->inUseWorkers[] = $pid;
        $process = $this->processes[$pid];

        if (count($this->inUseWorkers) > $this->inUsePeak) {
            $this->inUsePeak = count($this->inUseWorkers);
        }

        $this->logger->info("Marking worker $pid as in-use.");

        return $process;
    }

    /**
     * Get or create a new InputStream for a given $id.
     *
     * @param int $pid
     *
     * @return InputStream
     * @throws Exception
     */
    private function getInput(?int $pid): InputStream
    {
        if (!isset($this->processes[$pid])) {
            throw new RuntimeException("Process for PID=$pid not found.");
        }

        if (!isset($this->inputStreams[$pid])) {
            throw new RuntimeException("Input for PID=$pid not found.");
        }

        return $this->inputStreams[$pid];
    }

    /**
     * @param int $length
     *
     * @return string
     * @throws Exception
     */
    public function generateRandomString(int $length = null): string
    {
        $length = $length ?? 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     *
     * @return Process
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function startWorker(): Process
    {
        $this->logger->info('Starting new worker.');

        $executable = $this->kernel->getRootDir() . '/../bin/console';
        $environment = $this->kernel->getEnvironment();
        $process = new Process(
            "exec php $executable app:probe:worker --env=$environment"
        );
        $input = new InputStream();

        $process->setInput($input);
        $process->setTimeout(1500);
        $process->setIdleTimeout(300);

        $process->start(
            function ($type, $data) use ($process) {
                $pid = $process->getPid();

                if (isset($this->receiveBuffers[$pid])) {
                    $this->receiveBuffers[$pid] .= $data;
                } else {
                    $this->receiveBuffers[$pid] = '';
                }

                if (json_decode($this->receiveBuffers[$pid], true)) {
                    $this->handleResponse($type, $this->receiveBuffers[$pid]);

                    $this->releaseWorker($pid);
                }
            }
        );

        $pid = $process->getPid();
        $this->logger->info("Started Process/$pid");

        $this->processes[$pid] = $process;
        $this->inputStreams[$pid] = $input;
        $this->receiveBuffers[$pid] = '';

        $this->availableWorkers[] = $pid;

        return $process;
    }

    /**
     * @param $channel
     * @param $data
     */
    private function handleResponse($channel, $data): void
    {
        $this->logger->info("[$channel] data received");

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

        if (!array_key_exists($pid, $this->processes)) {
            $this->logger->critical('Received a response from an unknown process...');
        }

        switch ($type) {
            case 'exception':
                $this->logger->alert("Response ($status) from worker $pid returned an exception: " . $contents);
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
                        $queue = $this->queues[$key % $this->numberOfQueues];
                        $queue->enqueue($item);
                    }

                } else {
                    $this->logger->error("Response ($status) from worker $pid unexpected.");
                }
                break;

            case 'post-result':
                $found = false;
                foreach ($this->queues as $queue) {
                    if ($queue->getWorker() === $pid) {
                        $queue->result($status);
                        $this->receiveBuffers[$queue->getWorker()] = '';
                        $found = true;
                    }
                }
                if (!$found) {
                    $this->logger->warning("Could not find the queue for worker $pid");
                }

                break;

            case 'config-sync':
                if ($status === 200) {
                    $etag = $response['headers']['etag'];
                    $this->probeStore->updateConfig($contents, $etag);
                    $this->logger->info("Response ($status) from worker $pid config applied");
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
     * @param int $pid
     */
    private function releaseWorker(int $pid): void
    {
        $this->receiveBuffers[$pid] = '';

        foreach ($this->inUseWorkers as $index => $inUsePid) {
            if ($pid === $inUsePid) {
                unset($this->inUseWorkers[$index]);
            }
        }

        foreach ($this->availableWorkers as $index => $availablePid) {
            if ($pid === $availablePid) {
                $this->logger->warning("Worker $pid was apparently available when asked to be released, investigate!");
                unset($this->availableWorkers[$index]);
            }
        }

        unset($this->startTimes[$pid], $this->expectedRuntime[$pid]);

        $this->logger->info("Marking worker $pid as available.");
        $this->availableWorkers[] = $pid;
    }

    /**
     * Clean up tracking, inputs, processes and receive buffers.
     *
     * @param int $pid
     */
    private function cleanup(int $pid): void
    {
        if (array_key_exists($pid, $this->trackingIds)) {
            $this->logger->info("Process [$pid] cleanup started but no data received yet...");
        }

        if (isset($this->processes[$pid])) {
            $this->processes[$pid]->stop(3, SIGINT);
            $this->processes[$pid] = null;
            unset($this->processes[$pid]);
        }

        if (($key = array_search($pid, $this->availableWorkers, true)) !== false) {
            /** @var int $key */
            unset($this->availableWorkers[$key]);
        }

        if (($key = array_search($pid, $this->inUseWorkers, true)) !== false) {
            /** @var int $key */
            unset($this->inUseWorkers[$key]);
        }

        unset(
            $this->inputStreams[$pid],
            $this->receiveBuffers[$pid],
            $this->startTimes[$pid],
            $this->expectedRuntime[$pid]
        );
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
