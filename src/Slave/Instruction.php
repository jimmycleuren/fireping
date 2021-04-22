<?php

declare(strict_types=1);

namespace App\Slave;

use App\Probe\Probe;

class Instruction
{
    /**
     * @var Probe
     */
    protected $probe;

    protected $chunkSize;

    public function __construct(Probe $probe, int $chunkSize)
    {
        $this->probe = $probe;
        $this->chunkSize = $chunkSize;
    }

    public function getChunks(): \Generator
    {
        $chunks = array_chunk($this->probe->getDevices(), $this->chunkSize);
        foreach ($chunks as $devices) {
            yield $this->prepareInstruction($devices);
        }
    }

    public function prepareInstruction(array $devices): array
    {
        $serializedDevices = array_map(function (Device $device) {
            return $device->asArray();
        }, $devices);

        return $this->probe->getConfiguration($serializedDevices);
    }
}
