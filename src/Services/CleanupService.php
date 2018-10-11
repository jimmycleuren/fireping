<?php
/**
 * Created by PhpStorm.
 * User: kennyva
 * Date: 10/10/2018
 * Time: 12:47
 */

namespace App\Services;

use App\Entity\Device;
use App\Entity\StorageNode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CleanupService
{

    private $path;

    private $em;
    private $logger;

    private $inactiveDevices;
    private $activeDeviceIds;
    private $storedDeviceIds;
    private $storedActiveDevices;

    private $storageNode;
    private $activeProbes;
    private $activeGroups;


    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, ParameterBagInterface $params)
    {
        $this->path = $params->get('rrd_storage_path');

        $this->em = $em;
        $this->logger = $logger;

    }

    public function setStorageNode(StorageNode $storageNode){
        $this->storageNode = $storageNode;
    }

    public function cleanup(){

        echo 'Sitrep ' . PHP_EOL;

        $this->logger->info('Retrieving statistics...');
        $this->getCurrentSituation();

        echo 'Remove Inactive Devices ' . PHP_EOL;

        $this->logger->info('Removing inactive devices...');
        $this->removeInactiveDevices(10);

        echo 'Sitrep ' . PHP_EOL;

        $this->logger->info('Updating statistics...');
        $this->getCurrentSituation();

        echo 'Gather Active Probes ' . PHP_EOL;

        $this->logger->info('Gathering active probes...');
        $this->gatherActiveProbes();

        echo 'Remove Inactive Probes ' . PHP_EOL;

        $this->logger->info('Removing inactive probes...');
        $this->removeInactiveProbes();

        echo 'Gather Active Slave groups ' . PHP_EOL;

        $this->logger->info('Gathering active slave groups...');
        $this->gatherActiveSlaveGroups();

        echo 'Remove Inactive Slave Groups ' . PHP_EOL;

        $this->logger->info('Removing inactive slave groups...');
        $this->removeInactiveSlaveGroups();
    }

    protected function getCurrentSituation()
    {

        //create an array of existing directories
        $this->storedDeviceIds = $this->getDirContent($this->path);

        //remove newline
        array_pop($this->storedDeviceIds);

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


    protected function gatherActiveSlaveGroups(){

        if($this->storedActiveDevices === null){
            return null;
        }

        foreach ($this->storedActiveDevices as $device) {

            $deviceId = $device->getId();
            $activeGroups = $device->getActiveSlaveGroups();

            foreach ($activeGroups as $activeGroup){
                $this->activeGroups[$deviceId][] = $activeGroup->getid() . '.rrd';
            }

            $this->activeGroups[$deviceId] = array_unique($this->activeGroups[$deviceId]);

        }

    }

    protected function removeInactiveSlaveGroups(){

        if($this->activeProbes === null){
            return null;
        }

        $items = array_chunk($this->activeProbes, 10, true);
        $runningProcesses = [];
        $activeGroups = [];

        foreach ($items as $item){
            foreach ($item as $device => $probes){

                if(!isset($this->activeGroups[$device])){
                    continue;
                }

                if(\is_array($probes)){
                    $probes = implode(' ' . $this->path . '/' .$device. '/',  $probes);
                }

                $probes = $this->path . '/' .$device . '/' . $probes;

                $process = $this->generateProcess('ls ' . $probes);
                $process->start(function ($type, $buffer) {
                    if (Process::ERR === $type) {
                        echo 'ERR > '.$buffer;
                    }
                });

                $activeGroups[$device] = $this->activeGroups[$device];

                $runningProcesses[$device] = $process;

            }
        }

        while (count($runningProcesses)) {
            foreach ($runningProcesses as $device => $runningProcess) {

                    if (!$runningProcess->isRunning()) {

                        preg_match_all("/\d+.rrd/", $runningProcess->getOutput(), $storedGroups);

                        $storedGroups = array_unique($storedGroups[0]);

                        $difference = array_diff($storedGroups, $activeGroups[$device]);

                        if(empty($difference)){
                            unset($runningProcesses[$device], $activeGroups[$device]);
                            continue;
                        }


                        $param = '';

                        foreach($this->activeProbes[$device] as $probes => $probe){
                            foreach ($difference as $group){
                                $param .= $this->path .'/'. $device . '/' . $probe . '/' . $group . ' ';
                            }
                        }


                        $param = trim($param);

                        $process = $this->generateProcess('rm -rf '. $param);
                        $process->start(function ($type, $buffer) {
                            if (Process::ERR === $type) {
                                echo 'ERR > '.$buffer;
                            }
                        });

                        // specific process is finished, so we remove it
                        unset($runningProcesses[$device], $activeGroups[$device]);

                }

            }

        }


    }

    protected function gatherActiveProbes()
    {
        if($this->storedActiveDevices === null){
            return null;
        }

        foreach ($this->storedActiveDevices as $device) {

            $deviceId = $device->getId();
            $probes = $device->getActiveProbes();

            foreach ($probes as $probe){
                $this->activeProbes[$deviceId][] = $probe->getid();
            }

            $this->activeProbes[$deviceId] = array_unique($this->activeProbes[$deviceId]);
        }

    }

    protected function removeInactiveProbes()
    {
        if($this->activeProbes === null){
            return null;
        }

        $items = array_chunk($this->activeProbes, 10, true);
        $runningProcesses = [];
        $deviceProbes = [];

        foreach ($items as $item) {

            foreach ($item as $device => $probes) {

                $deviceProbes[$device] = $probes;

                $process = $this->generateProcess('ls '. $this->path . '/' . $device);
                $process->start(function ($type, $buffer) {
                    if (Process::ERR === $type) {
                        echo 'ERR > '.$buffer;
                    }
                });

                $runningProcesses[$device] = $process;
            }
        }

        while (count($runningProcesses)) {
            foreach ($runningProcesses as $device => $runningProcess) {
                if (! $runningProcess->isRunning()) {

                    $storedProbes = explode("\n", $runningProcess->getOutput());
                    array_pop($storedProbes);

                    $inactiveProbes = array_diff($storedProbes, $deviceProbes[$device]);

                    if(empty($inactiveProbes))
                    {
                        unset($runningProcesses[$device], $deviceProbes[$device]);
                        continue;
                    }

                    if(\is_array($inactiveProbes)){
                        $inactiveProbes = implode(' '.$this->path . '/' . $device.'/', $inactiveProbes);
                    }

                    $value = $this->path . '/' . $device.'/' . $inactiveProbes;

                    $process = $this->generateProcess('rm -rf '. $value);
                    $process->start(function ($type, $buffer) {
                        if (Process::ERR === $type) {
                            echo 'ERR > '.$buffer;
                        }
                    });

                    // specific process is finished, so we remove it
                    unset($runningProcesses[$device], $deviceProbes[$device]);
                }

            }

        }


    }

    protected function getDirContent(string $path){

        $process = $this->generateProcess('ls ' . $path);
        $process->run();

        if( !$process->isSuccessful()){
            throw new ProcessFailedException($process);
        }

        return explode("\n", $process->getOutput());
    }

    protected function removeInactiveDevices(int $maxProcesses = null){

        $nrInActive = $this->getInactiveDeviceCount();

        if($nrInActive === 0){
            return null;
        }

        if ($maxProcesses !== null && $maxProcesses < $nrInActive){
            $chunkSize = ($nrInActive - ($nrInActive % $maxProcesses)) / $maxProcesses;
            echo 'Chunksize: ' . $chunkSize . PHP_EOL;

            $items = array_chunk($this->inactiveDevices, $chunkSize);
        }else{
            $items = $this->inactiveDevices;
        }

        $runningProcesses = [];

        foreach ($items as $key => $value){

            if(\is_array($value)){
                $value = implode(' '.$this->path.'/', $value);
            }

            $value = $this->path . '/' . $value;

            $process = $this->generateProcess('rm -rf '. $value);
            $process->start(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > '.$buffer;
                }
            });

            $runningProcesses[] = $process;

        }

        while (count($runningProcesses)) {
            foreach ($runningProcesses as $i => $runningProcess) {

                // specific process is finished, so we remove it
                if (! $runningProcess->isRunning()) {
                    unset($runningProcesses[$i]);
                }

            }

            usleep(500000);
        }

    }

    public function getStoredDeviceCount(){
        return count($this->storedDeviceIds);
    }
    public function getActiveDeviceCount(){
        return count($this->activeDeviceIds);
    }
    public function getInactiveDeviceCount(){
        return count($this->inactiveDevices);
    }

    private function generateProcess($commands, string $cwd = null){

        if($this->storageNode !== null){
            $commands = 'ssh ' . $this->storageNode->getIp() . ' ' . $commands;
        }

        return new Process($commands, $cwd);

    }

}