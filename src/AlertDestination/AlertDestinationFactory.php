<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:03
 */

namespace App\AlertDestination;

use App\Entity\AlertDestination;
use Psr\Container\ContainerInterface;

class AlertDestinationFactory
{
    protected $container = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(AlertDestination $destination) : AlertDestinationInterface
    {
        $dest = $this->container->get("App\\AlertDestination\\".ucfirst($destination->getType()));
        if ($destination->getParameters()) {
            $dest->setParameters($destination->getParameters());
        }

        return $dest;
    }
}