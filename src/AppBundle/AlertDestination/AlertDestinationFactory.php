<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:03
 */

namespace AppBundle\AlertDestination;

use AppBundle\Entity\AlertDestination;
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
        $dest = $this->container->get("AppBundle\\AlertDestination\\".ucfirst($destination->getType()));
        $dest->setParameters($destination->getParameters());

        return $dest;
    }
}