<?php

declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\Probe;

use App\Model\Parameter\Probe\HttpParameters;
use PHPUnit\Framework\TestCase;

class HttpParametersTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDefault(array $in, array $expected): void
    {
        $arguments = HttpParameters::fromArray($in);

        self::assertInstanceOf(HttpParameters::class, $arguments);
        self::assertSame($expected, $arguments->asArray());
    }

    public function dataProvider(): array
    {
        return [
            [
                [],
                ['host' => null, 'path' => null, 'protocol' => null]
            ],
            [
                ['host' => 'www.google.be'],
                ['host' => 'www.google.be', 'path' => null, 'protocol' => null]
            ],
            [
                ['path' => '/test'],
                ['host' => null, 'path' => '/test', 'protocol' => null]
            ],
            [
                ['protocol' => 'https'],
                ['host' => null, 'path' => null, 'protocol' => 'https']
            ],
            [
                ['host' => 'www.google.be', 'path' => '/test', 'protocol' => 'https'],
                ['host' => "www.google.be", 'path' => "/test", 'protocol' => "https"]
            ],
        ];
    }
}