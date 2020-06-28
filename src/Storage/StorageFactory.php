<?php

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
        if (!isset($_ENV['STORAGE'])) {
            throw new \RuntimeException('Please specify the storage type in the STORAGE env variable');
        }
        switch ($_ENV['STORAGE']) {
            case 'rrd':
                return $this->rrdStorage;
            case 'rrdcached':
                return $this->rrdCachedStorage;
            case 'rrddistributed':
                return $this->rrdDistributedStorage;
            default:
                throw new \RuntimeException('Could not create storage '.$_ENV['STORAGE']);
        }
    }
}
