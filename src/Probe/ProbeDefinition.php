<?php

namespace App\Probe;

class ProbeDefinition
{
    protected $id;
    protected $type;
    protected $step;
    protected $samples;

    protected $args = [];
    protected $devices = [];

    public function __construct($id, $type, $step, $samples, array $args = null)
    {
        $this->setConfiguration($id, $type, $step, $samples, $args);
    }

    public function setConfiguration($id, $type, $step, $samples, array $args = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->step = $step;
        $this->samples = $samples;

        $this->args = isset($args) ? $args : [];
        // TODO: Move to arguments.
        $this->args['samples'] = $samples;
        $this->args['wait_time'] = intval($step / $samples) * 1000;
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

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArg($key, $value)
    {
        $this->args[$key] = $value;
    }

    public function getConfiguration($targets = null): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'step' => $this->step,
            'args' => $this->args,
            'targets' => $targets,
        ];
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
        $this->devices[$device->getId()]->setIp($device->getIp());
        $this->devices[$device->getId()]->setActive(true);
    }

    public function deactivateAllDevices()
    {
        foreach ($this->devices as $device) {
            $device->setActive(false);
        }
    }

    public function purgeAllInactiveDevices()
    {
        foreach ($this->devices as $key => $device) {
            if (!$device->isActive()) {
                unset($this->devices[$key]);
            }
        }
    }

    public function getSampleRate()
    {
        return $this->getStep() / $this->getSamples();
    }
}
