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
        $startOfWork = time();
        $this->logger->info(sprintf('worker %d has begun processing at %d', getmypid(), $startOfWork));

        foreach (['type', 'delay_execution'] as $parameter) {
            if (!isset($data[$parameter])) {
                $this->logger->info(sprintf('worker %d aborting because parameter %s is missing', getmypid(), $parameter));
                $this->sendResponse([
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $startOfWork,
                        'contents' => "parameter $parameter missing",
                    ],
                    'debug' => [
                        'runtime' => time() - $startOfWork,
                        'request' => $data,
                        'pid' => getmypid(),
                    ],
                ]);

                return;
            }
        }

        $this->logger->info(sprintf('worker %d received %s job from dispatcher', getmypid(), $data['type']));

        try {
            $command = $this->commandFactory->make($data['type'], $data);
            $this->logger->info(sprintf('worker %d %s command initialized', getmypid(), $data['type']));
        } catch (Exception $e) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $startOfWork,
                        'contents' => $e->getMessage(),
                    ],
                    'debug' => [
                        'runtime' => time() - $startOfWork,
                        'request' => $data,
                        'pid' => getmypid(),
                    ],
                ]
            );

            return;
        }

        $this->logger->info(sprintf('worker %d starting in %s second(s)', getmypid(), $data['delay_execution']));
        sleep($data['delay_execution']);
        $this->logger->info(sprintf('worker %d starting', getmypid()));

        try {
            $shellOutput = $command->execute();

            switch ($data['type']) {
                case PostStatsHttpWorkerCommand::class:
                case PostResultsHttpWorkerCommand::class:
                    $this->sendResponse([
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => [],
                        'body' => [
                            'timestamp' => $startOfWork,
                            'contents' => $shellOutput['contents'],
                            'raw' => $shellOutput,
                        ],
                        'debug' => [
                            'runtime' => time() - $startOfWork,
                            'pid' => getmypid(),
                        ],
                    ]);
                    break;

                case GetConfigHttpWorkerCommand::class:
                    $this->sendResponse([
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => [
                            'etag' => $shellOutput['etag'],
                        ],
                        'body' => [
                            'timestamp' => $startOfWork,
                            'contents' => $shellOutput['contents'],
                        ],
                        'debug' => [
                            'runtime' => time() - $startOfWork,
                            'pid' => getmypid(),
                        ],
                    ]);
                    break;

                case 'ping':
                case 'traceroute':
                case 'http':
                    $this->sendResponse([
                        'type' => 'probe',
                        'status' => 200,
                        'body' => [
                            'timestamp' => $startOfWork,
                            'contents' => [
                                $data['id'] => [
                                    'type' => $data['type'],
                                    'timestamp' => $data['timestamp'],
                                    'targets' => $shellOutput,
                                ],
                            ],
                        ],
                        'debug' => [
                            'runtime' => time() - $startOfWork,
                            //'request' => $data,
                            'pid' => getmypid(),
                        ],
                    ]);
                    break;
                default:
                    $this->sendResponse([
                        'type' => 'exception',
                        'status' => 500,
                        'body' => [
                            'timestamp' => $startOfWork,
                            'contents' => 'No answer defined for ' . $data['type'],
                        ],
                        'debug' => [
                            'runtime' => time() - $startOfWork,
                            'request' => $data,
                            'pid' => getmypid(),
                        ],
                    ]);
            }
        } catch (Exception $e) {
            $this->sendResponse([
                'type' => 'exception',
                'status' => 500,
                'body' => [
                    'timestamp' => $startOfWork,
                    'contents' => $e->getMessage() . ' on ' . $e->getFile() . ':' . $e->getLine(),
                ],
                'debug' => [
                    'runtime' => time() - $startOfWork,
                    'request' => $data,
                    'pid' => getmypid(),
                ],
            ]);

            return;
        }
    }

    /**
     * @param array $data
     */
    protected function sendResponse($data): void
    {
        $start = time();
        $this->logger->info(sprintf('worker %d sending %s response (took %d seconds)', getmypid(), $data['type'], $data['debug']['runtime']));
        $this->output->writeln(json_encode($data));
        $this->logger->info(sprintf('worker %d sent response (took %d seconds)', getmypid(), time() - $start));
    }
}
