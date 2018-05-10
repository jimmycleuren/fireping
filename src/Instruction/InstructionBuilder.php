<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 19/06/2017
 * Time: 9:49
 */

namespace App\Instruction;

use App\Probe\ProbeDefinition;

class InstructionBuilder
{
    public static function create(ProbeDefinition $probe, $size) : Instruction
    {
        return new Instruction($probe, $size);
    }
}