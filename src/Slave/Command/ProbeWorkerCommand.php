<?php

declare(strict_types=1);

namespace App\Slave\Command;

use App\Slave\Task\FetchConfiguration;
use App\Slave\Task\PublishResults;
use App\Slave\Task\PublishStatistics;
use App\Slave\Task\TaskFactory;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Stream\ReadableResourceStream;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ProbeWorkerCommand extends Command
{
    private OutputInterface $output;
    private string $receiveBuffer = '';
    private LoggerInterface $logger;
    private TaskFactory $taskFactory;

    public function __construct(LoggerInterface $logger, TaskFactory $taskFactory)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->taskFactory = $taskFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName('fireping:worker')
            ->setAliases(['app:probe:worker'])
            ->setDescription('Start the probe worker.')
            ->addOption(
                'max-runtime',
                'runtime',
                InputOption::VALUE_REQUIRED,
                'The amount of seconds the command can run before terminating itself',
                "0"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $read = new ReadableResourceStream(STDIN);

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
            Loop::addTimer($maxRuntime, function () {
                $this->logger->info('max runtime reached');
                Loop::stop();
            });
        }

        Loop::run();

        return 0;
    }

    /**
     * @throws \LogicException
     */
    private function process(array $data)
    {
        $startedAt = time();
        $this->logger->info(sprintf('worker %d has begun processing at %d', getmypid(), $startedAt));

        foreach (['type', 'delay_execution'] as $parameter) {
            if (!isset($data[$parameter])) {
                $errorMessage = sprintf('worker %d aborting because parameter %s is missing', getmypid(), $parameter);
                $this->logger->error($errorMessage);
                $this->sendResponse('exception', 0, $errorMessage);

                return;
            }
        }

        $type = $data['type'];
        $this->logger->info(sprintf('worker %d received %s job from dispatcher', getmypid(), $type));

        try {
            $task = $this->taskFactory->make($type, $data);
            $this->logger->info(sprintf('worker %d %s task initialized', getmypid(), $type));
        } catch (Exception $e) {
            $errorMessage = sprintf('worker %d fatal: ' . $e->getMessage());
            $this->logger->error($errorMessage);
            $this->sendResponse('exception', 0, $errorMessage);

            return;
        }

        $this->logger->info(sprintf('worker %d starting in %s second(s)', getmypid(), $data['delay_execution']));
        sleep($data['delay_execution']);
        $this->logger->info(sprintf('worker %d starting', getmypid()));

        try {
            $shellOutput = $task->execute();
            $this->logger->info(sprintf('worker %d finished (took %d second(s))', getmypid(), time() - $startedAt));

            switch ($type) {
                case PublishStatistics::class:
                case PublishResults::class:
                    $this->sendResponse($type, $shellOutput['code'], $shellOutput['contents']);
                    break;

                case FetchConfiguration::class:
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
                    $errorMessage = "no handler defined for $type";
                    $this->logger->error($errorMessage);
                    $this->sendResponse('exception', 0, $errorMessage);
            }
        } catch (Exception $e) {
            $errorMessage = sprintf(
                'worker %d fatal: %s (%s:%d)',
                getmypid(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $this->logger->error($errorMessage);
            $this->sendResponse('exception', 0, $errorMessage);

            return;
        }
    }

    /**
     * @param string $type
     * @param int $status
     * @param array $headers
     * @param array|string $contents
     */
    private function sendResponse(string $type, int $status, $contents, array $headers = []): void
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
