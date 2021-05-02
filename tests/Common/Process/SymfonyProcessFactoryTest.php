<?php

declare(strict_types=1);

namespace App\Tests\Common\Process;

use App\Common\Process\SymfonyProcess;
use App\Common\Process\SymfonyProcessFactory;
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