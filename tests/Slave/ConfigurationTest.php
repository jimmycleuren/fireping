<?php
declare(strict_types=1);

namespace App\Tests\Slave;

use App\Slave\Configuration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testConfigurationMustHaveHash()
    {
        $this->expectException(InvalidArgumentException::class);
        new Configuration('', []);
    }

    public function testConfigurationLoadsCorrectly()
    {
        $configuration = new Configuration('deadbeef', [
            1 => [
                'type' => 'ping',
                'samples' => 15,
                'step' => 60,
                'args' => [],
                'targets' => [
                    1 => '192.168.1.1',
                    2 => '192.168.1.2',
                ]
            ],
            2 => [
                'type' => 'ping',
                'samples' => 15,
                'step' => 60,
                'targets' => [
                    40 => '10.0.0.1',
                    50 => '10.0.0.2',
                ]
            ]
        ]);

        self::assertEquals('deadbeef', $configuration->getHash());
        self::assertEquals(4, $configuration->getTotalTargetCount());
        self::assertCount(2, $configuration->getProbes());
    }
}
