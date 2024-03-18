<?php

namespace App\Tests\Slave;

use App\Slave\Device;
use App\Slave\Instruction;
use App\Slave\Probe;
use PHPUnit\Framework\TestCase;

class InstructionTest extends TestCase
{
    public function testChunks(): void
    {
        $probeDefinition = new Probe(1, 'ping', 60, 15);
        $probeDefinition->addDevice(new Device('foo', '1.1.1.1'));
        $probeDefinition->addDevice(new Device('bar', '2.2.2.2'));

        $instruction = new Instruction($probeDefinition, 1);

        $counter = 0;
        foreach($instruction->getChunks() as $chunk) {
            $counter++;
        }

        $this->assertEquals(2, $counter);
    }
}
