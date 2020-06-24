<?php

namespace App\Instruction;


use App\Probe\ProbeDefinition;

interface InstructionInterface
{
    public function __construct(ProbeDefinition $probe, int $chunkSize);
    public function getChunks();
    public function prepareInstruction(array $devices);
}