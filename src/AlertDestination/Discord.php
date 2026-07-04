<?php

namespace App\AlertDestination;

use App\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Discord extends AlertDestinationInterface
{
    protected $client;
    protected $url;
    protected $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setParameters(array $parameters): void
    {
        if ($parameters && isset($parameters['url'])) {
            $this->url = $parameters['url'];
        }
    }

    public function trigger(Alert $alert)
    {
        return $this->send($alert, 16728374, '🔴');
    }

    public function clear(Alert $alert)
    {
        return $this->send($alert, 3066944, '🟢');
    }

    protected function send(Alert $alert, int $color, string $dot)
    {
        if (!$this->url) {
            return false;
        }
        try {
            $data = [
                'username' => 'fireping',
                'embeds' => [
                    [
                        'description' => $dot.' '.$this->getAlertMessage($alert),
                        'color' => $color,
                    ],
                ],
            ];
            $this->client->post($this->url, [RequestOptions::JSON => $data]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
