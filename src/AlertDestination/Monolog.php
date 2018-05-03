<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 19:46
 */

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
        $alertRule = $alert->getAlertRule();
        $device = $alert->getDevice()->getName();
        $group = $alert->getSlaveGroup()->getName();

        $this->logger->warning("FIREPING.ALERT: " . $alertRule->getName() . " on $device from $group");
    }

    public function clear(Alert $alert)
    {
        $alertRule = $alert->getAlertRule();
        $device = $alert->getDevice()->getName();
        $group = $alert->getSlaveGroup()->getName();

        $this->logger->warning("FIREPING.CLEAR: " . $alertRule->getName() . " on $device from $group");
    }
}