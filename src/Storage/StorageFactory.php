<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 17/08/2018
 * Time: 16:10
 */

namespace App\Storage;


class StorageFactory
{
    private $rrdStorage;
    private $rrdCachedStorage;
    private $rrdDistributedStorage;

    public function __construct(RrdStorage $rrdStorage, RrdCachedStorage $rrdCachedStorage, RrdDistributedStorage $rrdDistributedStorage)
    {
        $this->rrdStorage = $rrdStorage;
        $this->rrdCachedStorage = $rrdCachedStorage;
        $this->rrdDistributedStorage = $rrdDistributedStorage;
    }

    public function create()
    {
        switch ($_ENV['STORAGE']) {
            case 'rrd':
                return $this->rrdStorage;
            case 'rrdcached':
                return $this->rrdCachedStorage;
            case 'rrddistributed':
                return $this->rrdDistributedStorage;
            default:
                throw new \RuntimeException("Could not create storage ".$_ENV['STORAGE']);
        }
    }
}