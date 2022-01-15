<?php

declare(strict_types=1);

namespace App\Tests\Common\Process;

use App\Common\Process\ProcessFixture;
use App\Common\Process\SymfonyProcess;
use PHPUnit\Framework\TestCase;

class SymfonyProcessTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function test(array $command, ProcessFixture $fixture): void
    {
        $process = new SymfonyProcess($command);
        $exitCode = $process->run();

        self::assertEquals($fixture->getOutput(), $process->getOutput());
        self::assertEquals($fixture->getErrorOutput(), $process->getErrorOutput());
        self::assertEquals($fixture->isSuccessful(), $process->isSuccessful());
        self::assertEquals($fixture->getExitCode(), $exitCode);
    }

    public function dataProvider(): array
    {
        return [
            [['echo', 'hello'], new ProcessFixture("hello\n", '', true, 0)],
            [['this-command-does-not-exist'], new ProcessFixture('', "sh: 1: exec: this-command-does-not-exist: not found\n", false, 127)],
        ];
    }
}
