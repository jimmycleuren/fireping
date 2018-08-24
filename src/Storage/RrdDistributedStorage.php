<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Entity\StorageNode;
use App\Repository\StorageNodeRepository;
use Flexihash\Flexihash;
use Psr\Log\LoggerInterface;

class RrdDistributedStorage extends RrdCachedStorage
{
    private $storageNodes;

    public function __construct(LoggerInterface $logger, StorageNodeRepository $storageNodeRepository)
    {
        $this->logger = $logger;
        $this->hash = new Flexihash();

        $temp = $storageNodeRepository->findBy(['status' => StorageNode::STATUS_ACTIVE], ['id' => 'ASC']);
        foreach($temp as $node) {
            $this->storageNodes[$node->getId()] = $node;
            $this->hash->addTarget($node->getId());
        }
    }

    public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        $this->logger->info("Storing $device on $daemon");
        parent::store($device, $probe, $group, $timestamp, $data, $daemon);
    }

    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        parent::fetch($device, $probe, $group, $timestamp, $key, $function, $daemon);
    }

    public function fileExists($path, $daemon = null)
    {
        $daemon = "127.0.0.1:42217";

        return parent::fileExists($path, $daemon);
    }

    public function graph($options, $daemon = null)
    {
        $daemon = "127.0.0.1:42217";

        return parent::graph($options, $daemon);
    }

    public function getGraphValue($options, $daemon = null)
    {
        $daemon = "127.0.0.1:42217";

        return parent::getGraphValue($options, $daemon);
    }

    private function getStorageNode(Device $device) : StorageNode
    {
        $id = $this->hash->lookup($device->getId());
        return $this->storageNodes[$id];
    }
}