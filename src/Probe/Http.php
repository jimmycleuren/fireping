<?php
namespace App\Probe;

use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;


/**
 * Class Http
 * @package App\Probe
 */
class Http
{
    private $delay;
    private $logger;
    private $targets;
    private $samples;
    private $waitTime;

    public function __construct($data, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->delay = $data['delay_execution'];
        $this->targets = $data['targets'];
        $this->samples = $data['args']['samples'];
        $this->waitTime = $data['args']['wait_time'];
    }

    public function execute()
    {
        usleep($this->delay * 1000);

        $result = [];

        $client = new Client();
        for($i = 0; $i < $this->samples; $i++) {

            $start = microtime(true);
            $promises = [];
            foreach ($this->targets as $target) {
                $id = $target['id'];
                $promises[$target['id']] = $client->getAsync('http://'.$target['ip'], [
                    'on_stats' => function (TransferStats $stats) use ($id){
                        $this->times[$id] = $stats->getTransferTime() * 1000;
                    }
                ]);
            }

            $responses = Promise\settle($promises)->wait();
            foreach($responses as $id => $response) {
                if ($response['value']->getStatusCode() == 200) {
                    $result[$id][] = $this->times[$id];
                } else {
                    $result[$id][] = -1;
                }
            }

            $end = microtime(true);
            $duration = ($end - $start) * 1000000;
            $sleep = ($this->waitTime * 1000) - $duration;

            if ($sleep > 0 && $i < $this->samples) {
                usleep($sleep);
            }
        }

        return $result;
    }
}