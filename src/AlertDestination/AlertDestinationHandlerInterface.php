<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;

interface AlertDestinationHandlerInterface
{
    public function setParameters(array $parameters);

    public function trigger(Alert $alert);

    public function clear(Alert $alert);

    public function getAlertMessage(Alert $alert);
}