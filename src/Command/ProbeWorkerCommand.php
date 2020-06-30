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
use React\EventLoop\LoopInterface;
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
     * @var string
     */
    protected $temporaryBuffer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var int
     */
    protected $maxRuntime;

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
        $this->maxRuntime = (int) $input->getOption('max-runtime');

        $this->loop = Factory::create();

        $read = new ReadableResourceStream(STDIN, $this->loop);

        $read->on('data', function ($data) {
            $this->receiveBuffer .= $data;
            if (json_decode($this->receiveBuffer, true)) {
                $this->temporaryBuffer = $this->receiveBuffer;
                $this->receiveBuffer = '';
                $this->process($this->temporaryBuffer);
            }
        });

        if ($this->maxRuntime > 0) {
            $this->logger->info("Running for {$this->maxRuntime} seconds");
            $this->loop->addTimer($this->maxRuntime, function () use ($output) {
                $output->writeln('Max runtime reached');
                $this->loop->stop();
            });
        }

        $this->loop->run();

        return 0;
    }

    /**
     * @throws \LogicException
     */
    protected function process(string $data)
    {
        $startOfWork = time();
        $data = json_decode($data, true);
        $timestamp = $data['timestamp'] ?? null;

        if ($timestamp === null || !is_int($timestamp)) {
            $this->sendResponse([
                'type' => 'exception',
                'status' => 400,
                'body' => [
                    'timestamp' => $timestamp,
                    'contents' => 'Missing timestamp',
                ],
                'debug' => [
                    'runtime' => time() - $startOfWork,
                    'request' => $data,
                    'pid' => getmypid(),
                ],
            ]);
            throw new \RuntimeException('missing or invalid timestamp');
        }

        if (!$data) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => 'Invalid JSON Received.',
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

        if (!isset($data['type'])) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => 'Command type missing.',
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

        $str = 'COMMUNICATION_FLOW: Worker '.getmypid().' received a '.$data['type'].' instruction from master.';
        $this->logger->info($str);

        $command = null;
        try {
            $command = $this->commandFactory->make($data['type'], $data);
        } catch (Exception $e) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $timestamp,
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

        sleep($data['delay_execution']);

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
                            'timestamp' => $timestamp,
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
                    // This is a request to get the latest configuration from the master.
                    $this->sendResponse([
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => [
                            'etag' => $shellOutput['etag'],
                        ],
                        'body' => [
                            'timestamp' => $timestamp,
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
                            'timestamp' => $timestamp,
                            'contents' => [
                                $data['id'] => [
                                    'type' => $data['type'],
                                    'timestamp' => $timestamp,
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
                    $this->sendResponse(
                        [
                            'type' => 'exception',
                            'status' => 500,
                            'body' => [
                                'timestamp' => $timestamp,
                                'contents' => 'No answer defined for '.$data['type'],
                            ],
                            'debug' => [
                                'runtime' => time() - $startOfWork,
                                'request' => $data,
                                'pid' => getmypid(),
                            ],
                        ]
                    );
            }
        } catch (Exception $e) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 500,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => $e->getMessage().' on '.$e->getFile().':'.$e->getLine(),
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
    }

    /**
     * @param array $data
     */
    protected function sendResponse($data): void
    {
        $this->logger->info('COMMUNICATION_FLOW: Worker '.getmypid().' sent a '.$data['type'].' response.');
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}
