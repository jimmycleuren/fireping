<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 17/08/2018
 * Time: 16:10
 */

namespace App\Storage;

use Psr\Container\ContainerInterface;

class StorageFactory
{
    private $container;
    private $rrdStorage;
    private $rrdCachedStorage;

    public function __construct(ContainerInterface $container, RrdStorage $rrdStorage, RrdCachedStorage $rrdCachedStorage)
    {
        $this->container = $container;
        $this->rrdStorage = $rrdStorage;
        $this->rrdCachedStorage = $rrdCachedStorage;
    }

    public function create()
    {
        switch ($this->container->getParameter('storage')) {
            case 'rrd':
                return $this->rrdStorage;
            case 'rrdcached':
                return $this->rrdCachedStorage;
            default:
                throw new \RuntimeException("Could not create storage ".$this->container->getParameter('storage'));
        }
    }
}