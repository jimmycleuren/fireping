<?php

namespace App\Services;

use App\Entity\Device;
use App\Storage\RrdCachedStorage;
use App\Storage\RrdDistributedStorage;
use App\Storage\RrdStorage;
use App\Storage\StorageFactory;
use function count;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CleanupService
{
    /**
     * @var string
     */
    private $path;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $inactiveDevices;
    /**
     * @var array
     */
    private $activeDeviceIds;
    /**
     * @var array
     */
    private $storedDeviceIds;
    /**
     * @var array
     */
    private $storedActiveDevices;
    /**
     * @var array
     */
    private $activeProbes;
    /**
     * @var array
     */
    private $activeGroups;
    /**
     * @var RrdCachedStorage|RrdDistributedStorage|RrdStorage
     */
    private $storage;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, ParameterBagInterface $params, StorageFactory $storage)
    {
        $this->path = $params->get('rrd_storage_path');
        $this->em = $em;
        $this->logger = $logger;
        $this->storage = $storage->create();
    }

    /**
     * Main method that will be called
     * from the storage classes.
     */
    public function cleanup(): void
    {
        $this->logger->info('Retrieving statistics...');
        $this->setCurrentSituation();

        $this->logger->info('Removing inactive devices...');
        $this->removeInactiveDevices();

        $this->logger->info('Updating statistics...');
        $this->setCurrentSituation();

        $this->logger->info('Removing inactive probes...');
        $this->removeInactiveProbes();

        $this->logger->info('Removing inactive slave groups...');
        $this->removeInactiveSlaveGroups();
    }

    /**
     * This function paints a picture and sets variables
     * required for the cleanup.
     */
    private function setCurrentSituation(): void
    {
        //create an array of existing directories
        $this->storedDeviceIds = $this->storage->listItems($this->path);

        if (null === $this->storedDeviceIds) {
            $this->logger->info('No items found, directory is either clean or wrongly set');
            exit;
        }

        //get only the active devices based on previous result set
        $this->storedActiveDevices = $this->em->getRepository(Device::class)->findBy(['id' => $this->storedDeviceIds]);

        //get an array of id's for reference from the devices that are active
        //?? is this better than looping over each one of the storedActiveDevices ??
        $activeDevices = $this->em->createQuery('SELECT d.id FROM App:Device d')->getArrayResult();

        //Do the comparison to get the inactive devices
        $this->activeDeviceIds = array_column($activeDevices, 'id');
        $this->inactiveDevices = array_diff($this->storedDeviceIds, $this->activeDeviceIds);
    }

    protected function setActiveSlaveGroups(): void
    {
        if (null === $this->storedActiveDevices) {
            return;
        }

        foreach ($this->storedActiveDevices as $device) {
            $deviceId = $device->getId();
            $activeGroups = $device->getActiveSlaveGroups();

            foreach ($activeGroups as $activeGroup) {
                $this->activeGroups[$deviceId][] = $activeGroup->getid().'.rrd';
            }

            if (isset($this->activeGroups[$deviceId])) {
                $this->activeGroups[$deviceId] = array_unique($this->activeGroups[$deviceId]);
            }
        }
    }

    private function removeInactiveSlaveGroups(): void
    {
        if (null === $this->activeProbes) {
            return;
        }

        $this->setActiveSlaveGroups();

        foreach ($this->activeProbes as $device => $probes) {
            if (!isset($this->activeGroups[$device])) {
                continue;
            }

            foreach ($probes as $probe) {
                $path = $this->path.'/'.$device.'/'.$probe;

                $storedSlaves = $this->storage->listItems($path);

                if (null === $storedSlaves) {
                    continue;
                }

                $difference = array_diff($storedSlaves, $this->activeGroups[$device]);

                if (empty($difference)) {
                    continue;
                }

                $this->storage->remove($difference, $path);
            }
        }
    }

    private function setActiveProbes(): void
    {
        if (null === $this->storedActiveDevices) {
            return;
        }

        foreach ($this->storedActiveDevices as $device) {
            $deviceId = $device->getId();
            $probes = $device->getActiveProbes();

            foreach ($probes as $probe) {
                $this->activeProbes[$deviceId][] = $probe->getid();
            }

            if (isset($this->activeProbes[$deviceId])) {
                $this->activeProbes[$deviceId] = array_unique($this->activeProbes[$deviceId]);
            }
        }
    }

    private function removeInactiveProbes(): void
    {
        $this->setActiveProbes();

        if (null === $this->activeProbes) {
            return;
        }

        foreach ($this->activeProbes as $device => $probes) {
            $path = $this->path.'/'.$device;
            $storedProbes = $this->storage->listItems($path);
            if (null === $storedProbes) {
                continue;
            }

            $inactiveProbes = array_diff($storedProbes, $this->activeProbes[$device]);

            if (empty($inactiveProbes)) {
                continue;
            }

            $this->storage->remove($inactiveProbes, $path);
        }
    }

    private function removeInactiveDevices(): void
    {
        $nrInActive = $this->getInactiveDeviceCount();

        if (0 === $nrInActive) {
            return;
        }

        $items = array_chunk($this->inactiveDevices, 100);

        foreach ($items as $key => $value) {
            $this->storage->remove($value, $this->path);
        }
    }

    public function getStoredDeviceCount(): int
    {
        return \count($this->storedDeviceIds);
    }

    public function getActiveDeviceCount(): int
    {
        return \count($this->activeDeviceIds);
    }

    public function getInactiveDeviceCount(): int
    {
        return count($this->inactiveDevices);
    }
}
