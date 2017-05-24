<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 23/05/2017
 * Time: 16:38
 */

namespace AppBundle\Probe;


class ProbeDefinition
{
    protected $id;
    protected $type;
    protected $step;
    protected $samples;
    protected $interval;
    protected $devices = array();

    function __construct($id, $type, $step, $samples)
    {
        $this->id = $id;
        $this->type = $type;
        $this->step = $step;
        $this->samples = $samples;
        $this->interval = intval($step/$samples);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return mixed
     */
    public function getSamples()
    {
        return $this->samples;
    }

    /**
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param int $interval
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return array
     */
    public function getDevices()
    {
        return $this->devices;
    }

    public function getDeviceByIp($ip)
    {
        foreach ($this->devices as $device) {
            if ($device->getIp() === $ip) {
                return $device->getId();
            }
        }
        return null;
    }

    public function addDevice(DeviceDefinition $device)
    {
        if (!isset($this->devices[$device->getId()])) {
            print("[Probe:" . $this->id . "] Adding new Device: " . $device->getIp() . "\n");
            $this->devices[$device->getId()] = $device;
        }
        print("[Probe:".$this->id."] Activating Device: " . $device->getId() . "\n");
        $this->devices[$device->getId()]->setActive(true);
    }

    public function deactivateAllDevices()
    {
        foreach ($this->devices as $device)
        {
            $device->setActive(false);
        }
    }

    public function purgeAllInactiveDevices()
    {
        foreach ($this->devices as $key => $device)
        {
            print("[Probe:".$this->getId()."] Analysing ". $device->getId() . "[$key]\n");
            print("[Probe:".$this->getId()."] Device Active State: " . $device->isActive() . "\n");
            if (!$device->isActive())
            {
                print("[Probe:".$this->getId()."] Device is not active.\n");
                $name = $this->getId();
                $id = $device->getId();
                print("[Probe:".$this->getId()."] purging $id with key $key\n");
                unset($this->devices[$key]);
            }
        }
    }
}