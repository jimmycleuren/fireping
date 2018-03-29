<?php

namespace AppBundle\AlertDestination;

use AppBundle\Entity\Alert;
use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Transport\ApiClient;
use Psr\Log\LoggerInterface;

class Slack extends AlertDestinationInterface
{
    protected $client;
    protected $channel;
    protected $logger;

    public function __construct(ApiClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setParameters(array $parameters)
    {
        if ($parameters && isset($parameters['channel'])) {
            $this->channel = $parameters['channel'];
        }
        if ($parameters && isset($parameters['token'])) {
            $this->token = $parameters['token'];
        }
    }

    public function trigger(Alert $alert)
    {
        if (!$this->channel) {
            return false;
        }
        try {
            $payload = new ChatPostMessagePayload();
            $payload->setChannel('#'.$this->channel);
            $payload->setText("Triggered: ".$alert);
            $payload->setUsername('fireping');

            $this->client->send($payload, $this->token);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function clear(Alert $alert)
    {
        if (!$this->channel) {
            return false;
        }
        try {
            $payload = new ChatPostMessagePayload();
            $payload->setChannel('#'.$this->channel);
            $payload->setText("Cleared: ".$alert);
            $payload->setUsername('fireping');

            $this->client->send($payload, $this->token);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}