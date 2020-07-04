<?php

namespace App\Tests\App\Probe;

use App\Slave\Task\Http;
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
                ['id' => 1, 'ip' => 'www.google.be']
            ],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000,
                'host' => 'www.google.be',
            ]
        ]);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }

    public function testHttpTooLong()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $http = new Http($logger);
        $http->setArgs([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => 'www.google.be']
            ],
            'args' => [
                'samples' => 2,
                'wait_time' => 1,
                'host' => 'www.google.be',
            ]
        ]);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }

    public function testHttps()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $http = new Http($logger);
        $http->setArgs([
            'delay_execution' => 0,
            'targets' => [
                ['id' => 1, 'ip' => 'www.google.be']
            ],
            'args' => [
                'samples' => 2,
                'wait_time' => 10000,
                'protocol' => 'https',
            ]
        ]);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }
}
