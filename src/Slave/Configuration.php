<?php
declare(strict_types=1);

namespace App\Slave;

use App\Probe\DeviceDefinition;
use App\Probe\ProbeDefinition;
use InvalidArgumentException;

class Configuration
{
    /**
     * @var string
     */
    private $hash = '*';
    /**
     * @var ProbeDefinition[]
     */
    private $probes = [];

    public function __construct(string $hash= '*', array $probes = [])
    {
        if ($hash === '') {
            throw new InvalidArgumentException('Hash must not be an empty string.');
        }

        $this->hash = $hash;

        foreach ($probes as $id => $configuration) {
            $probe = new ProbeDefinition($id, $configuration['type'], $configuration['step'], $configuration['samples'], $configuration['args'] ?? []);
            foreach ($configuration['targets'] as $deviceId => $ip) {
                $device = new DeviceDefinition($deviceId, $ip);
                $probe->addDevice($device);
            }

            $this->probes[$id] = $probe;
        }
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return ProbeDefinition[]
     */
    public function getProbes(): array
    {
        return $this->probes;
    }

    public function getTotalTargetCount(): int
    {
        return \array_reduce($this->probes, static function ($carry, ProbeDefinition $probe) {
            return $carry + count($probe->getDevices());
        });
    }
}
