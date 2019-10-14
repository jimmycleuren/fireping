<?php
declare(strict_types=1);

namespace App\DependencyInjection;

use App\Probe\ProbeDefinition;
use App\Probe\DeviceDefinition;

class SlaveConfiguration
{
    private $probes = [];
    /**
     * @var string|null
     */
    private $etag;

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function setEtag(?string $etag): void
    {
        $this->etag = $etag;
    }

    private function addProbe(ProbeDefinition $probe): void
    {
        $this->probes[$probe->getId()] = $probe;
    }

    /**
     * @return ProbeDefinition[]
     */
    public function getProbes(): array
    {
        return $this->probes;
    }

    public function getProbeById($id): ?ProbeDefinition
    {
        return $this->probes[$id] ?? null;
    }

    public function getProbe($id, $type, $step, $samples, $args = null)
    {
        if ($probe = $this->getProbeById($id)) {
            $probe->setConfiguration($id, $type, $step, $samples, $args);
            return $probe;
        }

        $probe = new ProbeDefinition($id, $type, $step, $samples, $args);
        $this->addProbe($probe);
        return $probe;
    }

    public function getProbeDeviceCount($id): int
    {
        $probe = $this->getProbeById($id);
        return $probe !== null ? count($probe->getDevices()) : 0;
    }

    public function getAllProbesDeviceCount(): int
    {
        $total = 0;
        foreach ($this->getProbes() as $probe) {
            $total += count($probe->getDevices());
        }
        return $total;
    }

    private function deactivateAllDevices(): void
    {
        foreach ($this->getProbes() as $probe) {
            $probe->deactivateAllDevices();
        }
    }

    public function purgeAllInactiveDevices()
    {
        foreach ($this->getProbes() as $probe) {
            $probe->purgeAllInactiveDevices();
        }
    }

    public function updateConfig($configuration, $etag = null) {
        $this->deactivateAllDevices();
        foreach ($configuration as $id => $probeConfig) {
            // TODO: More checks to make sure all of this data is here?

            $type = $probeConfig['type'];
            $step = $probeConfig['step'];
            $samples = $probeConfig['samples'];
            $args = isset($probeConfig['args']) ? $probeConfig['args'] : null;

            $probe = $this->getProbe($id, $type, $step, $samples, $args);
            foreach ($probeConfig['targets'] as $hostname => $ip) {
                $device = new DeviceDefinition($hostname, $ip);
                $probe->addDevice($device);
            }
        }
        $this->purgeAllInactiveDevices();
        $this->setEtag($etag);
    }
}