<?php

namespace App\Storage;

class StorageFactory
{
    public function __construct(private readonly RrdStorage $rrdStorage, private readonly RrdCachedStorage $rrdCachedStorage, private readonly RrdDistributedStorage $rrdDistributedStorage)
    {
    }

    public function create()
    {
        if (!isset($_ENV['STORAGE'])) {
            throw new \RuntimeException('Please specify the storage type in the STORAGE env variable');
        }
        return match ($_ENV['STORAGE']) {
            'rrd' => $this->rrdStorage,
            'rrdcached' => $this->rrdCachedStorage,
            'rrddistributed' => $this->rrdDistributedStorage,
            default => throw new \RuntimeException('Could not create storage '.$_ENV['STORAGE']),
        };
    }
}
