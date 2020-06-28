<?php

declare(strict_types=1);

namespace App\Instruction;

use App\Probe\DeviceDefinition;
use App\Probe\ProbeDefinition;

class Instruction implements InstructionInterface
{
    /**
     * @var ProbeDefinition
     */
    protected $probe;

    protected $chunkSize;

    public function __construct(ProbeDefinition $probe, int $chunkSize)
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
        $serializedDevices = array_map(function (DeviceDefinition $device) {
            return $device->asArray();
        }, $devices);

        $instruction = $this->probe->getConfiguration($serializedDevices);

        return $instruction;
    }
}
