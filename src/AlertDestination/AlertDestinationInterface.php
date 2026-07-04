<?php

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
            return $alert->getAlertRule()->getMessageDown().': '.$alert->getDevice()->getName().' from '.$alert->getSlaveGroup()->getName();
        } else {
            return $alert->getAlertRule()->getMessageUp().': '.$alert->getDevice()->getName().' from '.$alert->getSlaveGroup()->getName();
        }
    }

    abstract public function trigger(Alert $alert);

    abstract public function clear(Alert $alert);
}
