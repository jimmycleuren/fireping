<?php

namespace App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Entity\StorageNode;
use App\Exception\RrdException;
use App\Repository\StorageNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Flexihash\Flexihash;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class RrdDistributedStorage extends RrdCachedStorage
{
    private $storageNodes;
    private $hash;

    public function __construct($path, LoggerInterface $logger, private readonly StorageNodeRepository $storageNodeRepo, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct($path, $logger);

        $this->hash = new Flexihash();

        $temp = $this->storageNodeRepo->findBy(['status' => StorageNode::STATUS_ACTIVE], ['id' => 'ASC']);
        foreach ($temp as $node) {
            $this->storageNodes[$node->getId()] = $node;
            $this->hash->addTarget(''.$node->getId());
        }
    }

    public function store(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $data, bool $addNewSources = false, $daemon = null): void
    {
        $node = $this->getStorageNode($device);
        $daemon = $node->getIp().':42217';

        parent::store($device, $probe, $group, $timestamp, $data, $addNewSources, $daemon);
    }

    public function getDatasources(Device $device, Probe $probe, SlaveGroup $group, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon = $node->getIp().':42217';

        return parent::getDatasources($device, $probe, $group, $daemon);
    }

    public function fetch(Device $device, Probe $probe, SlaveGroup $group, $timestamp, $key, $function, $daemon = null): mixed
    {
        $node = $this->getStorageNode($device);
        $daemon = $node->getIp().':42217';

        return parent::fetch($device, $probe, $group, $timestamp, $key, $function, $daemon);
    }

    public function fetchAll(Device $device, Probe $probe, SlaveGroup $group, $start, $end, $datasource, $function, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon  = $node->getIp().":42217";

        return parent::fetchAll($device, $probe, $group, $start, $end, $datasource, $function, $daemon);
    }

    public function fileExists(Device $device, $path, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon = $node->getIp().':42217';

        return parent::fileExists($device, $path, $daemon);
    }

    public function graph(Device $device, $options, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon = $node->getIp().':42217';

        return parent::graph($device, $options, $daemon);
    }

    public function getGraphValue(Device $device, $options, $daemon = null)
    {
        $node = $this->getStorageNode($device);
        $daemon = $node->getIp().':42217';

        return parent::getGraphValue($device, $options, $daemon);
    }

    private function getStorageNode(Device $device): StorageNode
    {
        $id = $this->hash->lookup(''.$device->getId());
        $node = $this->storageNodes[$id];

        if ($node != $device->getStorageNode()) {
            $this->logger->warning("Storage node for $device incorrect");
            if (null != $device->getStorageNode()) {
                $this->logger->warning("Trying to copy existing data for $device from ".$device->getStorageNode().' to '.$node);
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

    private function copyRrdFiles(Device $device, StorageNode $from, StorageNode $to): void
    {
        //first delete the folder in the destination node
        $process = new Process(['ssh', 'fireping@'.$to->getIp(), "'rm -rf /opt/fireping/var/rrd/".$device->getId()."'"]);
        $process->run();

        $error = $process->getErrorOutput();

        if ($error) {
            throw new \RuntimeException($error);
        }

        //next, copy the rrd files
        $src = 'fireping@'.$from->getIp().':/opt/fireping/var/rrd/'.$device->getId().'/';
        $dst = 'fireping@'.$to->getIp().':/opt/fireping/var/rrd/'.$device->getId().'/';
        $process = new Process(['scp', '-3',  '-r', $src, $dst]);
        $process->run();

        $error = $process->getErrorOutput();

        if ($error) {
            throw new \RuntimeException($error);
        } else {
            $this->logger->info("Data for $device copied from ".$from.' to '.$to);
        }

        //last, remove the rrd files from the original node to clean up space
        $process = new Process(['ssh', 'fireping@'.$from->getIp(), "'rm -rf /opt/fireping/var/rrd/".$device->getId()."'"]);
        $process->run();

        $error = $process->getErrorOutput();

        if ($error) {
            throw new \RuntimeException($error);
        }
    }

    /**
     * @param string $path
     *
     * @return array|string|null
     */
    public function listItems($path)
    {
        $output = '';
        foreach ($this->storageNodeRepo->findAll() as $storageNode) {
            $ip = $storageNode->getIp();
            echo 'Retrieving items from: '.$ip.PHP_EOL;

            $process = new Process(['ssh', $ip, 'ls', $path]);
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->logger->info($buffer);
                }
            });

            $output .= $process->getOutput();
        }

        if (empty($output)) {
            return null;
        }

        $contentArray = explode("\n", $output);
        $contentArray = array_filter(array_unique($contentArray));

        return $contentArray;
    }

    /**
     * @param array  $items
     * @param string $path
     */
    private function concatCollection($items, $path): array
    {
        return array_map(fn($item) => $this->concatPath($item, $path), $items);
    }

    /**
     * @param string $item
     * @param string $path
     */
    private function concatPath($item, $path): string
    {
        return $path.$item;
    }

    public function remove(array $items, string $path): void
    {
        $path = rtrim($path, '/').'/';
        $items = $this->concatCollection($items, $path);

        foreach ($this->storageNodeRepo->findAll() as $storageNode) {
            $ip = $storageNode->getIp();
            $process = new Process(array_merge(['ssh', $ip, 'rm', '-rf'], $items));
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->logger->info($buffer);
                }
            });
        }
    }

    protected function addDataSource(Device $device, $filename, $name, Probe $probe)
    {
        $node = $this->getStorageNode($device);

        $ds = sprintf(
            'DS:%s:%s:%s:%s:%s',
            $name,
            'GAUGE',
            $probe->getStep() * 2,
            0,
            'U'
        );

        $process = Process::fromShellCommandline('ssh fireping@'.$node->getIp()." 'rrdtool tune ".$this->path.$filename.' '.$ds."'");
        $process->run();
        $error = $process->getErrorOutput();

        if ($error) {
            throw new RrdException(trim($error));
        }
    }
}
