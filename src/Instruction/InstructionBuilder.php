<?php
declare(strict_types=1);

namespace App\Instruction;

use App\Probe\ProbeDefinition;

/**
 * Class InstructionBuilder
 *
 * @package App\Instruction
 */
class InstructionBuilder
{
    /**
     * @param ProbeDefinition $probe
     * @param int             $size
     *
     * @return Instruction
     */
    public static function create(ProbeDefinition $probe, int $size): Instruction
    {
        return new Instruction($probe, $size);
    }
}