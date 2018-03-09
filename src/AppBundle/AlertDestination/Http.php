<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:01
 */

namespace AppBundle\AlertDestination;

use AppBundle\Entity\Alert;
use GuzzleHttp\Client;

class Http extends AlertDestinationInterface
{
    protected $client;
    protected $url;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function setParameters($parameters)
    {
        $this->url = $parameters['url'];
    }

    public function trigger(Alert $alert)
    {
        $this->client->request('POST', $this->url, array('body' => $this->getData($alert, 'triggered')));
    }

    public function clear(Alert $alert)
    {
        $this->client->request('POST', $this->url, array('body' => $this->getData($alert, 'cleared')));
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