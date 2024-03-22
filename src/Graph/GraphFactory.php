<?php

namespace App\Graph;

use App\Storage\StorageFactory;

class GraphFactory
{
    public function __construct(private readonly StorageFactory $storageFactory)
    {
    }

    public function create(string $type): Graph
    {
        return match ($type) {
            'ping' => new PingGraph($this->storageFactory),
            'http' => new HttpGraph($this->storageFactory),
            'traceroute' => new TracerouteGraph($this->storageFactory),
            default => throw new \RuntimeException("Could not create graph of type $type"),
        };
    }
}
