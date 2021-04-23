<?php

declare(strict_types=1);

namespace App\Tests\App\Version;

use App\Common\Version\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDefault(string $in, string $expected): void
    {
        self::assertEquals($expected, (new Version($in))->asString());
    }

    public function dataProvider(): array
    {
        return [
            [' 124f124', '124f124'],
            ['v1.0', 'v1.0'],
            ["v1.0\n", 'v1.0'],
            ["v1.0\r\n", 'v1.0'],
            ['   ', ''],
            ['', ''],
        ];
    }
}