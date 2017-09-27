<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Processor;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;

abstract class Processor
{
    protected $storage;
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->storage = $container->get('storage.'.$container->getParameter('storage'));
    }

    abstract function storeResult(Device $device, Probe $probe, $timestamp, $data);
}