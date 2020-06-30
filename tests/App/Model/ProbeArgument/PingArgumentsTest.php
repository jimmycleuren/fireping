<?php

declare(strict_types=1);

namespace App\Tests\App\Model\ProbeArgument;

use App\Model\ProbeArgument\PingArguments;
use PHPUnit\Framework\TestCase;

class PingArgumentsTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDefault(string $json, array $expected)
    {
        $arguments = PingArguments::fromJsonString($json);
        self::assertEquals($expected, $arguments->asArray());
    }

    public function dataProvider()
    {
        return [
            ['{}', ['retries' => null, 'packetSize' => null]],
            ['{"retries": 5, "packetSize": null}', ['retries' => 5, 'packetSize' => null]],
            ['{"retries": 5}', ['retries' => 5, 'packetSize' => null]],
            ['{"packetSize": 5}', ['retries' => null, 'packetSize' => 5]],
            ['{"retries": 5, "packetSize": 10000}', ['retries' => 5, 'packetSize' => 10000]],
        ];
    }
}