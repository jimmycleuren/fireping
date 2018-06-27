<?php
declare(strict_types=1);

namespace App\Command;

use App\ShellCommand\CommandFactory;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProbeWorkerCommand extends ContainerAwareCommand
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

    /**
     * ProbeWorkerCommand constructor.
     *
     * @param LoggerInterface $logger
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->maxRuntime = $input->getOption('max-runtime');

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
    }

    /**
     * @param $data
     *
     * @throws \LogicException
     */
    protected function process(string $data)
    {
        $timestamp = time();

        if (!trim($data)) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => 'Input data not received.'
                    ],
                    'debug' => [
                        'runtime' => time() - $timestamp,
                        'request' => $data,
                        'pid' => getmypid()
                    ]
                ]
            );
            return;
        }

        $data = json_decode($data, true);

        if (!$data) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => 'Invalid JSON Received.'
                    ],
                    'debug' => [
                        'runtime' => time() - $timestamp,
                        'request' => $data,
                        'pid' => getmypid()
                    ]
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
                        'contents' => 'Command type missing.'
                    ],
                    'debug' => [
                        'runtime' => time() - $timestamp,
                        'request' => $data,
                        'pid' => getmypid()
                    ]
                ]
            );
            return;
        }

        $str = 'COMMUNICATION_FLOW: Worker ' . getmypid() . ' received a ' .
            $data['type'] . ' instruction from master.';
        $this->logger->info($str);

        $factory = new CommandFactory();
        $data['container'] = $this->getContainer();
        $command = null;
        try {
            $command = $factory->create($data['type'], $data);
        } catch (Exception $e) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 400,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => $e->getMessage()
                    ],
                    'debug' => [
                            'runtime' => time() - $timestamp,
                            'request' => $data,
                            'pid' => getmypid()
                    ]
                ]
            );
            return;
        }

        sleep($data['delay_execution']);

        try {
            $shellOutput = $command->execute();

            switch ($data['type']) {
                case 'post-result':
                    $this->sendResponse([
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => [],
                        'body' => [
                            'timestamp' => $timestamp,
                            'contents' => $shellOutput['contents'],
                            'raw' => $shellOutput
                        ],
                        'debug' => [
                            'runtime' => time() - $timestamp,
                            'pid' => getmypid()
                        ]
                    ]);
                    break;

                case 'config-sync':
                    // This is a request to get the latest configuration from the master.
                    $this->sendResponse([
                        'type' => $data['type'],
                        'status' => $shellOutput['code'],
                        'headers' => [
                            'etag' => $shellOutput['etag']
                        ],
                        'body' => [
                            'timestamp' => $timestamp,
                            'contents' => $shellOutput['contents']
                        ],
                        'debug' => [
                            'runtime' => time() - $timestamp,
                            'pid' => getmypid()
                        ]
                    ]);
                    break;

                case 'ping':
                case 'mtr':
                case 'traceroute':
                    $this->sendResponse([
                        'type' => 'probe',
                        'status' => 200,
                        'body' => [
                            'timestamp' => $timestamp,
                            'contents' => [
                                $data['id'] => [
                                    'type' => $data['type'],
                                    'timestamp' => $timestamp,
                                    'targets' => $shellOutput
                                ]
                            ]
                        ],
                        'debug' => [
                            'runtime' => time() - $timestamp,
                            //'request' => $data,
                            'pid' => getmypid()
                        ]
                    ]);
                    break;
            }
        } catch (Exception $e) {
            $this->sendResponse(
                [
                    'type' => 'exception',
                    'status' => 500,
                    'body' => [
                        'timestamp' => $timestamp,
                        'contents' => $e->getMessage()
                    ],
                    'debug' => [
                        'runtime' => time() - $timestamp,
                        'request' => $data,
                        'pid' => getmypid()
                    ]
                ]
            );
            return;
        }
    }

    /**
     * @param $data
     */
    protected function sendResponse($data): void
    {
        $this->logger->info('COMMUNICATION_FLOW: Worker ' . getmypid() . ' sent a ' . $data['type'] . ' response.');
        $json = json_encode($data);
        $this->output->writeln($json);
    }
}
