<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 14/06/2017
 * Time: 9:05
 */

namespace AppBundle\Instruction;


use AppBundle\Probe\ProbeDefinition;

interface InstructionInterface
{
    public function __construct(ProbeDefinition $probe, int $chunkSize);
    public function getChunks();
    public function prepareInstruction(array $devices);
}