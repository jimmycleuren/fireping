<?php

declare(strict_types=1);

namespace App\Tests\App\Process;

use App\Process\ProcessFixture;
use App\Process\SymfonyProcess;
use PHPUnit\Framework\TestCase;

class SymfonyProcessTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function test(array $command, ProcessFixture $fixture)
    {
        $process = new SymfonyProcess($command);
        $process->run();

        self::assertEquals($fixture->getOutput(), $process->getOutput());
        self::assertEquals($fixture->getErrorOutput(), $process->getErrorOutput());
        self::assertEquals($fixture->isSuccessful(), $process->isSuccessful());
    }

    public function dataProvider()
    {
        return [
            [['echo', 'hello'], new ProcessFixture("hello\n", '', true)],
            [['this-command-does-not-exist'], new ProcessFixture('', "sh: 1: exec: this-command-does-not-exist: not found\n", false)],
        ];
    }
}