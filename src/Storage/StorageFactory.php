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
    private $rrdDistributedStorage;

    public function __construct(ContainerInterface $container, RrdStorage $rrdStorage, RrdCachedStorage $rrdCachedStorage, RrdDistributedStorage $rrdDistributedStorage)
    {
        $this->container = $container;
        $this->rrdStorage = $rrdStorage;
        $this->rrdCachedStorage = $rrdCachedStorage;
        $this->rrdDistributedStorage = $rrdDistributedStorage;
    }

    public function create()
    {
        switch (getenv('STORAGE')) {
            case 'rrd':
                return $this->rrdStorage;
            case 'rrdcached':
                return $this->rrdCachedStorage;
            case 'rrddistributed':
                return $this->rrdDistributedStorage;
            default:
                throw new \RuntimeException("Could not create storage ".getenv('STORAGE'));
        }
    }
}