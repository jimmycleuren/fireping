<?php

namespace App\AlertDestination;

use App\Entity\Alert;
use Psr\Log\LoggerInterface;

class Monolog extends AlertDestinationInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function trigger(Alert $alert)
    {
        $this->logger->warning("FIREPING.ALERT: " . $this->getAlertMessage($alert));
    }

    public function clear(Alert $alert)
    {
        $this->logger->warning("FIREPING.CLEAR: " . $this->getAlertMessage($alert));
    }
}