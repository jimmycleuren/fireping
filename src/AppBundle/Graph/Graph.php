<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Graph;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Storage\RrdStorage;
use Psr\Container\ContainerInterface;

abstract class Graph
{
    protected $storage;
    protected $container;

    public function __construct(ContainerInterface $container, RrdStorage $rrdStorage)
    {
        $this->container = $container;

        if ($container->getParameter('storage') == "rrd") {
            $this->storage = $rrdStorage;
        }
    }

    abstract function getSummaryGraph(Device $device, Probe $probe);
}