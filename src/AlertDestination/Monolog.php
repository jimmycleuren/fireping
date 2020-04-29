<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use Psr\Log\LoggerInterface;

class Monolog extends AlertDestinationHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function trigger(Alert $alert): void
    {
        $this->logger->warning("FIREPING.ALERT: " . $this->getAlertMessage($alert));
    }

    public function clear(Alert $alert): void
    {
        $this->logger->warning("FIREPING.CLEAR: " . $this->getAlertMessage($alert));
    }

    public function setParameters(array $parameters): void
    {
    }
}