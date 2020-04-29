<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;

interface AlertDestinationHandlerInterface
{
    /**
     * @param array<string, string|int|null> $parameters
     */
    public function setParameters(array $parameters): void;

    public function trigger(Alert $alert): void;

    public function clear(Alert $alert): void;

    public function getAlertMessage(Alert $alert): string;
}