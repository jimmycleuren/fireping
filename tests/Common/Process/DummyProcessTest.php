<?php

declare(strict_types=1);

namespace App\Tests\Common\Process;

use App\Common\Process\DummyProcess;
use App\Common\Process\ProcessFixture;
use PHPUnit\Framework\TestCase;

class DummyProcessTest extends TestCase
{
    public function test(): void
    {
        $process = new DummyProcess('output', '', true);

        self::assertEquals('output', $process->getOutput());
        self::assertEquals('', $process->getErrorOutput());
        self::assertTrue($process->isSuccessful());
        self::assertEquals(0, $process->run());
    }

    public function testFromFixture(): void
    {
        $process = DummyProcess::fromFixture(new ProcessFixture('', 'error', false, 15));

        self::assertEquals('', $process->getOutput());
        self::assertEquals('error', $process->getErrorOutput());
        self::assertFalse($process->isSuccessful());
        self::assertEquals(15, $process->run());
    }
}
