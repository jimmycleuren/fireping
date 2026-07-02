<?php

namespace App\AlertDestination;

use App\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Gotify extends AlertDestinationInterface
{
    protected $client;
    protected $url;
    protected $token;
    protected $priority = 5;
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
        if ($parameters && isset($parameters['token'])) {
            $this->token = $parameters['token'];
        }
        if ($parameters && isset($parameters['priority'])) {
            $this->priority = (int) $parameters['priority'];
        }
    }

    public function trigger(Alert $alert)
    {
        return $this->send($alert);
    }

    public function clear(Alert $alert)
    {
        return $this->send($alert);
    }

    protected function send(Alert $alert)
    {
        if (!$this->url || !$this->token) {
            return false;
        }
        try {
            $data = [
                'title' => 'fireping',
                'message' => $this->getAlertMessage($alert),
                'priority' => $this->priority,
            ];
            $this->client->post(rtrim((string) $this->url, '/').'/message', [
                RequestOptions::HEADERS => ['X-Gotify-Key' => $this->token],
                RequestOptions::JSON => $data,
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
