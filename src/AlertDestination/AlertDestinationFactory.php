<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\AlertDestination;
use Psr\Container\ContainerInterface;

class AlertDestinationFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(AlertDestination $destination): AlertDestinationHandlerInterface
    {
        $dest = $this->container->get("App\\AlertDestination\\" . ucfirst($destination->getType()));
        if ($destination->getParameters()) {
            $dest->setParameters($destination->getParameters());
        }

        return $dest;
    }
}