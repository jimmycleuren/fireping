<?php

namespace App\AlertDestination;

use App\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Slack extends AlertDestinationInterface
{
    protected $client;
    protected $channel;
    protected $url;
    protected $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setParameters(array $parameters)
    {
        if ($parameters && isset($parameters['channel'])) {
            $this->channel = $parameters['channel'];
        }
        if ($parameters && isset($parameters['url'])) {
            $this->url = $parameters['url'];
        }
    }

    public function trigger(Alert $alert)
    {
        if (!$this->url) {
            return false;
        }
        try {
            $data = array(
                'username' => "fireping",
                'attachments' => array(
                    array(
                        "fallback" => $this->getAlertMessage($alert),
                        "color" => "#FF4136",
                        "text" => $this->getAlertMessage($alert)
                    )
                )
            );
            if ($this->channel) {
                $data['channel'] = "#".$this->channel;
            }
            $this->client->post($this->url, array(RequestOptions::JSON => $data));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function clear(Alert $alert)
    {
        if (!$this->url) {
            return false;
        }
        try {
            $data = array(
                'username' => "fireping",
                'attachments' => array(
                    array(
                        "fallback" => $this->getAlertMessage($alert),
                        "color" => "#2ECC40",
                        "text" => $this->getAlertMessage($alert)
                    )
                )
            );
            if ($this->channel) {
                $data['channel'] = "#".$this->channel;
            }
            $this->client->post($this->url, array(RequestOptions::JSON => $data));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}