<?php

namespace App\Tests\Slave\Task;

use App\Slave\OutputFormatter\PingOutputFormatter;
use App\Slave\Task\Ping;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PingTest extends TestCase
{
    public function testPingUnorderedArguments(): void
    {
        $ping = new Ping(new PingOutputFormatter());
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

    public function testMissingArgument(): void
    {
        $ping = new Ping(new PingOutputFormatter());
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

    public function testNoTargets(): void
    {
        $ping = new Ping(new PingOutputFormatter());

        $this->expectException(\Exception::class);

        $ping->setArgs([
            'delay_execution' => 0,
            'targets' => [],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000
            ]
        ]);

        $ping->execute();
    }

    public function testGetType(): void
    {
        $ping = new Ping(new PingOutputFormatter());

        $this->assertEquals('ping', $ping->getType());
    }
}
