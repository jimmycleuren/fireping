<?php
declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use App\Exception\ClearException;
use App\Exception\TriggerException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class Http extends AlertDestinationInterface
{
    private ClientInterface $client;
    private string $url;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function setParameters(array $parameters)
    {
        if ($parameters) {
            $this->url = $parameters['url'];
        }
    }

    public function trigger(Alert $alert)
    {
        if (!isset($this->url)) {
            throw new TriggerException('URL missing');
        }

        try {
            $this->client->request('POST', $this->url, [
                RequestOptions::JSON => $this->getData($alert, 'triggered')
            ]);
        } catch (GuzzleException $e) {
            throw new TriggerException($e->getMessage(), 0, $e);
        }
    }

    public function clear(Alert $alert)
    {
        if (!isset($this->url)) {
            throw new ClearException('URL missing');
        }

        try {
            $this->client->request('POST', $this->url, [
                RequestOptions::JSON => $this->getData($alert, 'cleared')
            ]);
        } catch (GuzzleException $e) {
            throw new ClearException($e->getMessage(), 0, $e);
        }
    }

    protected function getData(Alert $alert, $state)
    {
        return [
            'device' => [
                'id' => $alert->getDevice()->getId(),
                'name' => $alert->getDevice()->getName(),
            ],
            'source' => [
                'id' => $alert->getSlaveGroup()->getId(),
                'name' => $alert->getSlaveGroup()->getName(),
            ],
            'rule' => [
                'id' => $alert->getAlertRule()->getId(),
                'name' => $alert->getAlertRule()->getName(),
            ],
            'state' => $state,
        ];
    }
}
