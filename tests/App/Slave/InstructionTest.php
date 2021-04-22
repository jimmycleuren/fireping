<?php

namespace App\Tests\Slave;

use App\Probe\DeviceDefinition;
use App\Probe\ProbeDefinition;
use App\Slave\Instruction;
use PHPUnit\Framework\TestCase;

class InstructionTest extends TestCase
{
    public function testChunks()
    {
        $probeDefinition = new ProbeDefinition(1, 'ping', 60, 15);
        $probeDefinition->addDevice(new DeviceDefinition('foo', '1.1.1.1'));
        $probeDefinition->addDevice(new DeviceDefinition('bar', '2.2.2.2'));

        $instruction = new Instruction($probeDefinition, 1);

        $counter = 0;
        foreach($instruction->getChunks() as $chunk) {
            $counter++;
        }

        $this->assertEquals(2, $counter);
    }
}