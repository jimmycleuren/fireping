<?php

namespace App\Tests\App\Probe;

use App\OutputFormatter\PingOutputFormatter;
use App\Probe\Ping;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class PingTest extends TestCase
{
    use ProphecyTrait;

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
                'samples' => 2,
            ]
        ]);

        $result = $ping->execute();

        $this->assertEquals(2, count($result[1]));
    }
}