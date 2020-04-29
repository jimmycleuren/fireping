<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Http extends AlertDestinationHandler
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setParameters(array $parameters): void
    {
        if ($parameters) {
            $this->url = (string) $parameters['url'];
        }
    }

    public function trigger(Alert $alert): void
    {
        if (!$this->url) {
            return;
        }
        try {
            $this->client->post($this->url, array(RequestOptions::JSON => $this->getData($alert, 'triggered')));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return array<string, string|array<string, int|string>>
     */
    protected function getData(Alert $alert, string $state): array
    {
        return array(
            'device' => array(
                'id' => $alert->getDevice()->getId(),
                'name' => $alert->getDevice()->getName()
            ),
            'source' => array(
                'id' => $alert->getSlaveGroup()->getId(),
                'name' => $alert->getSlaveGroup()->getName()
            ),
            'rule' => array(
                'id' => $alert->getAlertRule()->getId(),
                'name' => $alert->getAlertRule()->getName()
            ),
            'state' => $state
        );
    }

    public function clear(Alert $alert): void
    {
        if (!$this->url) {
            return;
        }
        try {
            $this->client->post($this->url, array(RequestOptions::JSON => $this->getData($alert, 'cleared')));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}