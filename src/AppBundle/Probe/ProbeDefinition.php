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

    protected $args = array();
    protected $devices = array();

    function __construct($id, $type, $step, $samples, array $args = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->step = $step;
        $this->samples = $samples;

        $this->args = isset($args) ? $args : array();
        $this->args['samples'] = $samples;
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

    public function getArgs() : array
    {
        return $this->args;
    }

    public function getConfiguration($targets = null) : array
    {
        return array(
            'id' => $this->id,
            'type' => $this->type,
            'step' => $this->step,
            'args' => $this->args,
            'targets' => $targets,
        );
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
            $this->devices[$device->getId()] = $device;
        }
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
            if (!$device->isActive())
            {
                unset($this->devices[$key]);
            }
        }
    }
}