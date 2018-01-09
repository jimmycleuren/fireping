<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Storage;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Entity\SlaveGroup;
use Psr\Container\ContainerInterface;

abstract class Storage
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    abstract function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data);

    abstract function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function);
}