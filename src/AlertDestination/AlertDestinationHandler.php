<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;

abstract class AlertDestinationHandler implements AlertDestinationHandlerInterface
{
    abstract public function setParameters(array $parameters);

    abstract public function trigger(Alert $alert);

    abstract public function clear(Alert $alert);

    public function getAlertMessage(Alert $alert)
    {
        if ($alert->getActive()) {
            return $alert->getAlertRule()->getMessageDown() . ": " . $alert->getDevice()->getName() . " from " . $alert->getSlaveGroup()->getName();
        }

        return $alert->getAlertRule()->getMessageUp() . ": " . $alert->getDevice()->getName() . " from " . $alert->getSlaveGroup()->getName();
    }
}