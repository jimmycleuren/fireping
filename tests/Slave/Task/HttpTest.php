<?php

namespace App\Tests\Slave\Task;

use App\Slave\Task\Http;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HttpTest extends TestCase
{
    public function testHttp(): void
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
                'wait_time' => 2000,
                'host' => 'www.google.be',
            ]
        ]);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }

    public function testHttpTooLong(): void
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

    public function testHttps(): void
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
                'wait_time' => 2000,
                'protocol' => 'https',
            ]
        ]);

        $result = $http->execute();

        $this->assertEquals(2, count($result[1]));
    }
}
