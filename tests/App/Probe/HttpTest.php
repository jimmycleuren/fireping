<?php

namespace Tests\App\Probe;

use App\Probe\Http;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HttpTest extends TestCase
{
    public function testHttp()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $http = new Http([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => '216.58.211.99']
            ],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000
            ]
        ], $logger);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }
}