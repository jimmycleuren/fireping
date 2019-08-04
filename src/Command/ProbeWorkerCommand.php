<?php
declare(strict_types=1);

namespace App\Command;

use App\ShellCommand\CommandFactory;
use App\ShellCommand\GetConfigHttpWorkerCommand;
use App\ShellCommand\PostResultsHttpWorkerCommand;
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
    protected $stdout;
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
    private $factory;
    /**
     * @var int
     */
    private $pid;
    /**
     * @var int
     */
    private $startedAt;
    /**
     * @var array
     */
    private $request;

    /**
     * @throws LogicException
     */
    public function __construct(LoggerInterface $logger, CommandFactory $factory)
    {
        $this->logger = $logger;
        $this->factory = $factory;
        $this->pid = getmypid();
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->stdout = $output;
        $maxRuntime = (int)$input->getOption('max-runtime');

        $loop = Factory::create();
        $read = new ReadableResourceStream(STDIN, $loop);

        $read->on('data', function ($data) {
            $this->receiveBuffer .= $data;
            if ($request = json_decode($this->receiveBuffer, true)) {
                $this->process($request);
            }
        });

        if ($maxRuntime > 0) {
            $this->logger->info(sprintf('Worker[%s]: running for %s seconds', $this->pid, $maxRuntime));
            $loop->addTimer($maxRuntime, function () use ($loop) {
                // TODO(): Not JSON, so dispatcher will not understand.
                $this->stdout->writeln('Max runtime reached');
                $loop->stop();
            });
        }

        $loop->run();
    }

    private function getResponse($response, string $type = 'probe', int $status = 200, array $headers = []): array
    {
        return [
            'type' => $type,
            'status' => $status,
            'headers' => $headers,
            'body' => [
                'timestamp' => $this->startedAt,
                'contents' => $response
            ],
            'debug' => [
                'runtime' => time() - $this->startedAt,
                'pid' => $this->pid
            ]
        ];
    }

    private function getFailedResponse($response, int $code = 400): array
    {
        if ($response instanceof Exception) {
            $response = (string) $response;
        }

        return $this->getResponse($response, 'exception', $code);
    }


    /**
     * @throws \LogicException
     */
    private function process(array $request)
    {
        $this->request = $request;
        $this->startedAt = time();

        if (!isset($request['type'])) {
            $this->sendResponse($this->getFailedResponse('Command type missing.'));
            return;
        }

        $requestType = $request['type'];
        $this->logger->info(sprintf('Worker[%s]: processing instruction type=%s', $this->pid, $requestType));

        try {
            $command = $this->factory->make($requestType, $request);
        } catch (Exception $exception) {
            $this->sendResponse($this->getFailedResponse($exception));
            return;
        }

        sleep($request['delay_execution']);

        try {
            $output = $command->execute();
            $code = $output['code'] ?? 0;
            $contents = $output['contents'] ?? '';

            switch ($requestType) {
                case PostResultsHttpWorkerCommand::class:
                    $this->sendResponse($this->getResponse($contents, $requestType, $code));
                    break;

                case GetConfigHttpWorkerCommand::class:
                    $headers = ['etag' => $output['etag']];
                    $this->sendResponse($this->getResponse($contents, $requestType, $code, $headers));
                    break;

                default:
                    $inner = [
                        $request['id'] => [
                            'type' => $requestType,
                            'timestamp' => $this->startedAt,
                            'targets' => $output
                        ]
                    ];
                    $this->sendResponse($this->getResponse($inner));
                    break;
            }
        } catch (Exception $exception) {
            $this->sendResponse($this->getFailedResponse($exception, 500));
            return;
        }
    }

    protected function sendResponse(array $data): void
    {
        $this->logger->info(sprintf('Worker[%s]: sending response of type=%s', $this->pid, $data['type']));
        $this->stdout->writeln(json_encode($data));
    }
}
