<?php

namespace App\Processor;

use App\AlertDestination\AlertDestinationFactory;
use App\Storage\Cache;
use App\Storage\StorageFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProcessorFactory
{
    private $storageFactory;
    private $alertDestinationFactory;
    private $logger;
    private $entityManager;
    private $cache;

    public function __construct(StorageFactory $storageFactory, AlertDestinationFactory $alertDestinationFactory, LoggerInterface $logger, EntityManagerInterface $entityManager, Cache $cache)
    {
        $this->storageFactory = $storageFactory;
        $this->alertDestinationFactory = $alertDestinationFactory;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }

    public function create(string $type) : Processor
    {
        switch($type) {
            case 'ping': return new PingProcessor($this->storageFactory, $this->alertDestinationFactory, $this->logger, $this->entityManager, $this->cache);
            case 'http': return new HttpProcessor($this->storageFactory, $this->alertDestinationFactory, $this->logger, $this->entityManager, $this->cache);
            case 'traceroute': return new TracerouteProcessor($this->storageFactory, $this->alertDestinationFactory, $this->logger, $this->entityManager, $this->cache);
            default: throw new \RuntimeException("Could not create processor of type $type");
        }
    }
}