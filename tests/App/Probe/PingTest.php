<?php

namespace App\Tests\App\Probe;

use App\OutputFormatter\PingOutputFormatter;
use App\Slave\Task\Ping;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PingTest extends TestCase
{
    public function testPingUnorderedArguments()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $ping = new Ping($logger, new PingOutputFormatter());
        $ping->setArgs([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => 'www.google.be']
            ],
            'args' => [
                'wait_time' => 1000,
                'samples' => 1,
            ]
        ]);

        $result = $ping->execute();

        $this->assertEquals(1, count($result[1]));
    }
}