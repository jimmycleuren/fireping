<?php

namespace App\Graph;

use App\Storage\StorageFactory;

class GraphFactory
{
    private $storageFactory;

    public function __construct(StorageFactory $storageFactory)
    {
        $this->storageFactory = $storageFactory;
    }

    public function create(string $type): Graph
    {
        switch ($type) {
            case 'ping': return new PingGraph($this->storageFactory);
            case 'http': return new HttpGraph($this->storageFactory);
            case 'traceroute': return new TracerouteGraph($this->storageFactory);
            default: throw new \RuntimeException("Could not create graph of type $type");
        }
    }
}
