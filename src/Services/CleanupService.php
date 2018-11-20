<?php
/**
 * Created by PhpStorm.
 * User: kennyva
 * Date: 10/10/2018
 * Time: 12:47
 */

namespace App\Services;

use App\Entity\Device;
use App\Storage\StorageFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class CleanupService
 * @package App\Services
 */
class CleanupService
{

    /** @var string */
    private $path;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $inactiveDevices;
    /** @var array */
    private $activeDeviceIds;
    /** @var array */
    private $storedDeviceIds;

    /** @var array */
    private $storedActiveDevices;

    /** @var array */
    private $activeProbes;
    /** @var array */
    private $activeGroups;

    /** @var \App\Storage\RrdCachedStorage|\App\Storage\RrdDistributedStorage|\App\Storage\RrdStorage  */
    private $storage;


    /**
     * CleanupService constructor.
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @param ParameterBagInterface $params
     * @param StorageFactory $storage
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, ParameterBagInterface $params, StorageFactory $storage)
    {
        $this->path = $params->get('rrd_storage_path');

        $this->em = $em;
        $this->logger = $logger;
        $this->storage = $storage->create();

    }

    /**
     * Main method that will be called
     * from the storage classes
     */
    public function cleanup(): void
    {
        echo PHP_EOL . 'Sitrep ' . PHP_EOL;

        $this->logger->info('Retrieving statistics...');
        $this->setCurrentSituation();

        echo 'Remove Inactive Devices ' . PHP_EOL;

        $this->logger->info('Removing inactive devices...');
        $this->removeInactiveDevices();

        echo 'Sitrep ' . PHP_EOL;

        $this->logger->info('Updating statistics...');
        $this->setCurrentSituation();

        echo 'Remove Inactive Probes ' . PHP_EOL;

        $this->logger->info('Removing inactive probes...');
        $this->removeInactiveProbes();

        echo 'Remove Inactive Slave Groups ' . PHP_EOL;

        $this->logger->info('Removing inactive slave groups...');
        $this->removeInactiveSlaveGroups();
    }

    /**
     * This function paints a picture and sets variables
     * required for the cleanup
     */
    private function setCurrentSituation(): void
    {

        //create an array of existing directories
        $this->storedDeviceIds = $this->storage->listItems($this->path);

        if($this->storedDeviceIds === null){
            echo 'No devices listed, directory is either clean or wrongly set, exiting...' . PHP_EOL;
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

        echo 'Stored Devices: ' . $this->getStoredDeviceCount() . PHP_EOL;
        echo 'Active Devices: ' . $this->getActiveDeviceCount() . PHP_EOL;
        echo 'Inactive Devices: ' . $this->getInactiveDeviceCount() . PHP_EOL;
    }


    /**
     * gathers the slave groups for all active devices
     * that are stored on the filesystem
     */
    protected function setActiveSlaveGroups(): void
    {

        if($this->storedActiveDevices === null){
            return;
        }

        foreach ($this->storedActiveDevices as $device) {

            $deviceId = $device->getId();
            $activeGroups = $device->getActiveSlaveGroups();

            foreach ($activeGroups as $activeGroup){
                $this->activeGroups[$deviceId][] = $activeGroup->getid() . '.rrd';
            }

            if (isset($this->activeGroups[$deviceId]))
            {
                $this->activeGroups[$deviceId] = array_unique($this->activeGroups[$deviceId]);
            }
        }

    }

    /**
     * removes everything that is not part of the active
     * groups array
     */
    private function removeInactiveSlaveGroups(): void
    {

        if($this->activeProbes === null){
            return;
        }

        $this->setActiveSlaveGroups();


        foreach ($this->activeProbes as $device => $probes){

            if(!isset($this->activeGroups[$device])){
                continue;
            }

            foreach ($probes as $probe){
                $path = $this->path . '/' . $device . '/' . $probe;

                $storedSlaves = $this->storage->listItems($path);

                if($storedSlaves === null){
                    continue;
                }

                $difference = array_diff($storedSlaves, $this->activeGroups[$device]);

                if(empty($difference)){
                    continue;
                }

                $this->storage->remove($difference, $path);

            }

        }

    }

    /**
     * collect the probes from active devices in storage
     */
    private function setActiveProbes(): void
    {
        if($this->storedActiveDevices === null){
            return;
        }

        foreach ($this->storedActiveDevices as $device) {

            $deviceId = $device->getId();
            $probes = $device->getActiveProbes();

            foreach ($probes as $probe){
                $this->activeProbes[$deviceId][] = $probe->getid();
            }

            if (isset($this->activeProbes[$deviceId]))
            {
                $this->activeProbes[$deviceId] = array_unique($this->activeProbes[$deviceId]);
            }

        }

    }

    /**
     * remove all inactive probes that are not part
     * of the active probes array
     */
    private function removeInactiveProbes(): void
    {

        $this->setActiveProbes();

        if($this->activeProbes === null){
            return;
        }


        foreach ($this->activeProbes as $device => $probes) {

            $path = $this->path . '/' . $device;
            $storedProbes = $this->storage->listItems($path);
            if($storedProbes === null){
                continue;
            }

            $inactiveProbes = array_diff($storedProbes, $this->activeProbes[$device]);

            if(empty($inactiveProbes))
            {
                continue;
            }

            $this->storage->remove($inactiveProbes, $path);

        }

    }

    /**
     * remove inactive devices
     */
    private function removeInactiveDevices(): void
    {

        $nrInActive = $this->getInactiveDeviceCount();

        if($nrInActive === 0){
            return;
        }

        echo 'Removing ' . $nrInActive . ' devices...' . PHP_EOL;

        $items = array_chunk($this->inactiveDevices, 100);

        foreach ($items as $key => $value){

            $this->storage->remove($value, $this->path);

        }

    }

    /**
     * @return int
     */
    public function getStoredDeviceCount() : int{
        return \count($this->storedDeviceIds);
    }

    /**
     * @return int
     */
    public function getActiveDeviceCount() : int{
        return \count($this->activeDeviceIds);
    }

    /**
     * @return int
     */
    public function getInactiveDeviceCount() : int{
        return \count($this->inactiveDevices);
    }


}