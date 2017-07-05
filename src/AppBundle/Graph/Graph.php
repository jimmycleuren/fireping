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

abstract class Graph
{
    protected $storage;
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->storage = $container->get('storage.'.$container->getParameter('storage'));
    }

    abstract function getSummaryGraph(Device $device, Probe $probe);
}