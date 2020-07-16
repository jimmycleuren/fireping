<?php

declare(strict_types=1);

namespace App\Tests\App\Process;

use App\Process\DummyProcess;
use App\Process\ProcessFixture;
use PHPUnit\Framework\TestCase;

class DummyProcessTest extends TestCase
{
    public function test()
    {
        $process = new DummyProcess('output', '', true);

        self::assertEquals('output', $process->getOutput());
        self::assertEquals('', $process->getErrorOutput());
        self::assertTrue($process->isSuccessful());
    }

    public function testFromFixture()
    {
        $process = DummyProcess::fromFixture(new ProcessFixture('', 'error', false));

        self::assertEquals('', $process->getOutput());
        self::assertEquals('error', $process->getErrorOutput());
        self::assertFalse($process->isSuccessful());
    }
}