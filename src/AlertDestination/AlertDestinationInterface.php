<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 19:52
 */

namespace App\AlertDestination;

use App\Entity\Alert;

abstract class AlertDestinationInterface
{
    public function setParameters(array $parameters)
    {

    }

    protected function getAlertMessage(Alert $alert)
    {
        if ($alert->getActive()) {
            return $alert->getAlertRule()->getMessageDown().": ".$alert->getDevice()->getName()." from ".$alert->getSlaveGroup()->getName();
        } else {
            return $alert->getAlertRule()->getMessageUp().": ".$alert->getDevice()->getName()." from ".$alert->getSlaveGroup()->getName();
        }
    }

    public abstract function trigger(Alert $alert);
    public abstract function clear(Alert $alert);
}