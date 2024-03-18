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
    }

    public function testFromFixture(): void
    {
        $process = DummyProcess::fromFixture(new ProcessFixture('', 'error', false));

        self::assertEquals('', $process->getOutput());
        self::assertEquals('error', $process->getErrorOutput());
        self::assertFalse($process->isSuccessful());
    }
}