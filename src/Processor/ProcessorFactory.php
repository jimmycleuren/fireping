<?php

namespace App\Processor;

use App\AlertDestination\AlertDestinationFactory;
use App\Storage\Cache;
use App\Storage\StorageFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProcessorFactory
{
    public function __construct(private readonly StorageFactory $storageFactory, private readonly AlertDestinationFactory $alertDestinationFactory, private readonly LoggerInterface $logger, private readonly EntityManagerInterface $entityManager, private readonly Cache $cache)
    {
    }

    public function create(string $type): Processor
    {
        return match ($type) {
            'ping' => new PingProcessor($this->storageFactory, $this->alertDestinationFactory, $this->logger, $this->entityManager, $this->cache),
            'http' => new HttpProcessor($this->storageFactory, $this->alertDestinationFactory, $this->logger, $this->entityManager, $this->cache),
            'traceroute' => new TracerouteProcessor($this->storageFactory, $this->alertDestinationFactory, $this->logger, $this->entityManager, $this->cache),
            default => throw new \RuntimeException("Could not create processor of type $type"),
        };
    }
}
