<?php

declare(strict_types=1);

namespace App\Tests\Slave\Process;

use App\Process\DummyProcess;
use App\Process\DummyProcessFactory;
use App\Process\ProcessFixture;
use PHPUnit\Framework\TestCase;

class DummyFactoryTest extends TestCase
{
    public function test()
    {
        $factory = new DummyProcessFactory();
        $factory->addFixture(sha1(serialize(['echo', 'hello'])), new ProcessFixture("hello\n", '', true));
        $process = $factory->create(['echo', 'hello']);

        self::assertInstanceOf(DummyProcess::class, $process);
    }
}