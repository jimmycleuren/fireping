<?php
declare(strict_types=1);

namespace App\Probe;

use App\ShellCommand\CommandInterface;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class Http implements CommandInterface
{
    private $args;
    private $delay;
    private $logger;
    private $targets;
    private $samples;
    private $waitTime;
    private $times;
    private $allowedCodes = [
        200,
        301,
        302
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): array
    {
        usleep($this->delay * 1000);

        $result = [];

        $options  = [
            'timeout' => ($this->waitTime / 1000) * 0.9,
            'allow_redirects' => false,
            'headers' => [],
        ];

        if (isset($this->args['host'])) {
            $options['headers']['Host'] = $this->args['host'];
        }
        $client = new Client($options);

        $path = isset($this->args['path']) ? $this->args['path'] : "/";

        for($i = 0; $i < $this->samples; $i++) {
            $start = microtime(true);
            $promises = [];
            foreach ($this->targets as $target) {
                $id = $target['id'];
                $promises[$target['id']] = $client->getAsync('http://'.$target['ip'].$path, [
                    'on_stats' => function (TransferStats $stats) use ($id){
                        $this->times[$id] = $stats->getTransferTime() * 1000;
                    },
                    'http_errors' => false
                ]);
            }

            $responses = Promise\settle($promises)->wait();
            foreach($responses as $id => $response) {
                try {
                    $result[$id][] = ['time' => $this->times[$id], 'code' => $response['value']->getStatusCode()];
                } catch (\Exception $exception) {
                    $this->logger->error(get_class($exception) . ": " . $exception->getMessage());
                }
            }

            $end = microtime(true);
            $duration = ($end - $start) * 1000000;
            $sleep = ($this->waitTime * 1000) - $duration;

            if ($sleep > 0 && $i < $this->samples) {
                usleep((int) $sleep);
            } elseif($sleep < 0) {
                $this->logger->warning("HTTP probe did not have enough time, sleep time was $sleep");
            }
        }

        return $result;
    }

    public function setArgs(array $args): void
    {
        $this->delay = $args['delay_execution'];
        $this->targets = $args['targets'];
        $this->samples = $args['args']['samples'];
        $this->waitTime = $args['args']['wait_time'];
        $this->args = $args['args'];
    }

    public function getType(): string
    {
        return 'http';
    }
}