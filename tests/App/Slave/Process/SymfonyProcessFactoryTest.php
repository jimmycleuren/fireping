<?php

declare(strict_types=1);

namespace App\Tests\Slave\Process;

use App\Process\SymfonyProcess;
use App\Process\SymfonyProcessFactory;
use PHPUnit\Framework\TestCase;

class SymfonyProcessFactoryTest extends TestCase
{
    public function test()
    {
        $factory = new SymfonyProcessFactory();
        $process = $factory->create(['echo', 'hello']);

        self::assertInstanceOf(SymfonyProcess::class, $process);
    }
}