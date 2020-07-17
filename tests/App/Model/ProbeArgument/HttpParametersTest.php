<?php

declare(strict_types=1);

namespace App\Tests\App\Model\ProbeArgument;

use App\Model\Parameter\Probe\HttpParameters;
use PHPUnit\Framework\TestCase;

class HttpParametersTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDefault(string $json, array $expected)
    {
        $arguments = HttpParameters::fromJsonString($json);
        self::assertSame($expected, $arguments->asArray());
    }

    public function dataProvider()
    {
        return [
            ['{}', ['host' => null, 'path' => null, 'protocol' => null]],
            ['{"host": "www.google.be"}', ['host' => "www.google.be", 'path' => null, 'protocol' => null]],
            ['{"path": "/test"}', ['host' => null, 'path' => "/test", 'protocol' => null]],
            ['{"protocol": "https"}', ['host' => null, 'path' => null, 'protocol' => "https"]],
            ['{"host": "www.google.be", "path": "/test", "protocol": "https"}', ['host' => "www.google.be", 'path' => "/test", 'protocol' => "https"]],
        ];
    }
}