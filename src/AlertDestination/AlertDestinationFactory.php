<?php

namespace App\AlertDestination;

use App\Entity\AlertDestination\AlertDestination;
use App\Entity\AlertDestination\EmailDestination;
use App\Entity\AlertDestination\LogDestination;
use App\Entity\AlertDestination\SlackDestination;
use App\Entity\AlertDestination\WebhookDestination;
use Psr\Container\ContainerInterface;

class AlertDestinationFactory
{
    private const MAP = [
        SlackDestination::class => Slack::class,
        WebhookDestination::class => Http::class,
        EmailDestination::class => Mail::class,
        LogDestination::class => Monolog::class
    ];

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(AlertDestination $destination): AlertDestinationInterface
    {
        $handler = $this->mapTo(get_class($destination));
        $handler->setParameters($destination->asArray());

        return $handler;
    }

    public function mapTo(string $className): AlertDestinationInterface
    {
        return $this->container->get(self::MAP[$className]);
    }
}
