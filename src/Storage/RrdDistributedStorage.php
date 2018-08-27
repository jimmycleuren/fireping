<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Entity\StorageNode;
use App\Repository\StorageNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Flexihash\Flexihash;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class RrdDistributedStorage extends RrdCachedStorage
{
    private $entityManager;
    private $storageNodes;
    private $hash;

    public function __construct(LoggerInterface $logger, StorageNodeRepository $storageNodeRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct(null, $logger);

        $this->hash = new Flexihash();
        $this->entityManager = $entityManager;

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

    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        parent::fetch($device, $probe, $group, $timestamp, $key, $function, $daemon);
    }

    public function fileExists(Device $device, $path, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        return parent::fileExists($device, $path, $daemon);
    }

    public function graph(Device $device, $options, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        return parent::graph($device, $options, $daemon);
    }

    public function getGraphValue(Device $device, $options, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        return parent::getGraphValue($device, $options, $daemon);
    }

    private function getStorageNode(Device $device) : StorageNode
    {
        $id = $this->hash->lookup($device->getId());
        $node = $this->storageNodes[$id];

        if ($node != $device->getStorageNode()) {
            $this->logger->warning("Storage node for $device incorrect");
            if ($device->getStorageNode() != null) {
                $this->logger->warning("Trying to copy existing data for $device from " . $device->getStorageNode() . " to " . $node);
                $this->copyRrdFiles($device, $device->getStorageNode(), $node);
            } else {
                $this->logger->warning("No previous storage node defined for $device");
            }
            $device->setStorageNode($node);
            $this->entityManager->persist($device);
            $this->entityManager->flush();
        }

        return $node;
    }

    private function copyRrdFiles(Device $device, StorageNode $from, StorageNode $to)
    {
        $src = 'fireping@'.$from->getIp().':/opt/fireping/var/rrd/'.$device->getId().'/';
        $dst = 'fireping@'.$to->getIp().':/opt/fireping/var/rrd/'.$device->getId().'/';
        $process = new Process("scp -r $src $dst");
        $process->run();

        $output = $process->getOutput();
        $error = $process->getErrorOutput();

        $this->logger->info($output);
        if ($error) {
            throw new \RuntimeException($error);
        }
    }
}