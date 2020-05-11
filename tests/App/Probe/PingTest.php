<?php

namespace App\Tests\App\Probe;

use App\OutputFormatter\PingOutputFormatter;
use App\Probe\Http;
use App\Probe\Ping;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PingTest extends TestCase
{
    public function testPing()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $ping = new Ping($logger, new PingOutputFormatter());
        $ping->setArgs([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => '8.8.8.8']
            ],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000
            ]
        ]);

        $result = $ping->execute();

        $this->assertEquals(2, count($result[1]));
    }

    public function testMissingArrument()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();
        $ping = new Ping($logger, new PingOutputFormatter());
        $ping->setArgs([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => '8.8.8.8']
            ],
            'args' => [
                'wait_time' => 10000
            ]
        ]);

        $this->expectException(\Exception::class);

        $ping->execute();
    }

    public function testNoTargets()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();
        $ping = new Ping($logger, new PingOutputFormatter());
        $ping->setArgs([
            'delay_execution' => 0,
            'targets' => [],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000
            ]
        ]);

        $this->expectException(\Exception::class);

        $ping->execute();
    }

    public function testGetType()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();
        $ping = new Ping($logger, new PingOutputFormatter());

        $this->assertEquals('ping', $ping->getType());
    }
}