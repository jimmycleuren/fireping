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
use AppBundle\Entity\SlaveGroup;
use Symfony\Component\Cache\Adapter\RedisAdapter;

abstract class Processor
{
    protected $storage;
    protected $container;
    protected $cache;

    public function __construct($container)
    {
        $this->container = $container;
        $this->storage = $container->get('storage.'.$container->getParameter('storage'));
        $this->logger = $container->get('logger');
        $this->em = $this->container->get('doctrine')->getManager();

        $connection = RedisAdapter::createConnection("redis://localhost");
        $this->cache = new RedisAdapter($connection, 'fireping', 3600 * 24);
    }

    abstract function storeResult(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data);
}