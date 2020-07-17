<?php

declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\Probe;

use App\Model\Parameter\Probe\PingParameters;
use PHPUnit\Framework\TestCase;

class PingParametersTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDefault(array $in, array $expected): void
    {
        $arguments = PingParameters::fromArray($in);

        self::assertInstanceOf(PingParameters::class, $arguments);
        self::assertEquals($expected, $arguments->asArray());
    }

    public function dataProvider(): array
    {
        return [
            [
                [],
                ['retries' => null, 'packetSize' => null]
            ],
            [
                ['retries' => 5, 'packetSize' => null],
                ['retries' => 5, 'packetSize' => null]
            ],
            [
                ['retries' => 5],
                ['retries' => 5, 'packetSize' => null]
            ],
            [
                ['packetSize' => 5],
                ['retries' => null, 'packetSize' => 5]
            ],
            [
                ['retries' => 5, 'packetSize' => 10000],
                ['retries' => 5, 'packetSize' => 10000]
            ],
        ];
    }
}