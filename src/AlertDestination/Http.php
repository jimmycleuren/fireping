<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:01
 */

namespace App\AlertDestination;

use App\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class Http extends AlertDestinationInterface
{
    protected $client;
    protected $url;
    protected $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setParameters(array $parameters)
    {
        if ($parameters) {
            $this->url = $parameters['url'];
        }
    }

    public function trigger(Alert $alert)
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

    public function clear(Alert $alert)
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

    protected function getData(Alert $alert, $state)
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
}