<?php

namespace App\Tests\App\Probe;

use App\Probe\Http;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class HttpTest extends TestCase
{
    use ProphecyTrait;

    public function testHttp()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $http = new Http($logger);
        $http->setArgs([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => '216.58.211.99']
            ],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000
            ]
        ]);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }
}