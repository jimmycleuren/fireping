<?php

declare(strict_types=1);

namespace App\Command;

use App\ShellCommand\CommandFactory;
use App\ShellCommand\GetConfigHttpWorkerCommand;
use App\ShellCommand\PostResultsHttpWorkerCommand;
use App\ShellCommand\PostStatsHttpWorkerCommand;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProbeWorkerCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $receiveBuffer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CommandFactory
     */
    private $commandFactory;

    /**
     * @throws LogicException
     */
    public function __construct(LoggerInterface $logger, CommandFactory $commandFactory)
    {
        $this->logger = $logger;
        $this->commandFactory = $commandFactory;

        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setName('app:probe:worker')
            ->setDescription('Start the probe worker.')
            ->addOption(
                'max-runtime',
                'runtime',
                InputOption::VALUE_REQUIRED,
                'The amount of seconds the command can run before terminating itself',
                0
            );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $loop = Factory::create();

        $read = new ReadableResourceStream(STDIN, $loop);

        $read->on('data', function ($data) {
            $this->receiveBuffer .= $data;
            if ($in = json_decode($this->receiveBuffer, true)) {
                $this->receiveBuffer = '';
                $this->process($in);
            }
        });

        $maxRuntime = (int)$input->getOption('max-runtime');
        if ($maxRuntime > 0) {
            $this->logger->info("Running for {$maxRuntime} seconds");
            $loop->addTimer($maxRuntime, function () use ($loop) {
                $this->logger->info('Worker max runtime reached.');
                $loop->stop();
            });
        }

        $loop->run();

        return 0;
    }

    /**
     * @throws \LogicException
     */
    protected function process(array $data)
    {
        $startedAt = time();
        $this->logger->info(sprintf('worker %d has begun processing at %d', getmypid(), $startedAt));

        foreach (['type', 'delay_execution'] as $parameter) {
            if (!isset($data[$parameter])) {
                $this->logger->error(sprintf('worker %d aborting because parameter %s is missing', getmypid(), $parameter));

                return;
            }
        }

        $type = $data['type'];
        $this->logger->info(sprintf('worker %d received %s job from dispatcher', getmypid(), $type));

        try {
            $command = $this->commandFactory->make($type, $data);
            $this->logger->info(sprintf('worker %d %s command initialized', getmypid(), $type));
        } catch (Exception $e) {
            $this->logger->error(sprintf('worker %d fatal: ' . $e->getMessage()));

            return;
        }

        $this->logger->info(sprintf('worker %d starting in %s second(s)', getmypid(), $data['delay_execution']));
        sleep($data['delay_execution']);
        $this->logger->info(sprintf('worker %d starting', getmypid()));

        try {
            $shellOutput = $command->execute();
            $this->logger->info(sprintf('worker %d finished (took %d second(s))', getmypid(), time() - $startedAt));

            switch ($type) {
                case PostStatsHttpWorkerCommand::class:
                case PostResultsHttpWorkerCommand::class:
                    $this->sendResponse($type, $shellOutput['code'], $shellOutput['contents']);
                    break;

                case GetConfigHttpWorkerCommand::class:
                    $headers = ['etag' => $shellOutput['etag']];
                    $this->sendResponse($type, $shellOutput['code'], $shellOutput['contents'], $headers);
                    break;

                case 'ping':
                case 'traceroute':
                case 'http':
                    $contents = [
                        $data['id'] => [
                            'type' => $type,
                            'timestamp' => $data['timestamp'],
                            'targets' => $shellOutput,
                        ],
                    ];
                    $this->sendResponse('probe', 200, $contents);
                    break;

                default:
                    $this->logger->error("no handler defined for $type");
            }
        } catch (Exception $e) {
            $this->logger->error(sprintf('worker %d fatal: ' . $e->getMessage()));

            return;
        }
    }

    /**
     * @param string $type
     * @param int $status
     * @param array $headers
     * @param array|string $contents
     */
    protected function sendResponse(string $type, int $status, $contents, array $headers = []): void
    {
        $pid = getmypid();

        $data = [
            'pid' => $pid,
            'type' => $type,
            'status' => $status,
            'headers' => $headers,
            'contents' => $contents,
        ];

        $start = time();
        $out = json_encode($data);
        $this->logger->info(sprintf('worker %d sending %s response (%d bytes)', $pid, $type, strlen($out)));
        $this->output->writeln($out);
        $this->logger->info(sprintf('worker %d sent response (took %d seconds)', $pid, time() - $start));
    }
}
