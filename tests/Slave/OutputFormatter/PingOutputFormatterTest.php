<?php

declare(strict_types=1);

namespace App\Tests\Slave\OutputFormatter;

use App\Slave\OutputFormatter\PingOutputFormatter;
use PHPUnit\Framework\TestCase;

class PingOutputFormatterTest extends TestCase
{
    /**
     * @testdox
     * @dataProvider inputProvider
     *
     * Note, fping already filters out hostnames that don't resolve to anything, so we don't have to test it.
     * Example:
     *  root@7bacc83c139b:/app# fping -C 10 dirty
     *  dirty: No address associated with hostname
     * Example:
     *  root@7bacc83c139b:/app# fping -C 10 -q dirty 127.0.0.1 # dirty is excluded from result.
     *  127.0.0.1 : 0.07 0.09 0.10 0.09 0.09 0.10 0.05 0.08 0.09 0.14
     */
    public function testFormatterParsesAllDataCorrectly(array $input, array $expected): void
    {
        $formatter = new PingOutputFormatter();
        $this->assertEquals($expected, $formatter->format($input));
    }

    public function inputProvider(): array
    {
        return [
            [
                [
                    '8.8.8.8    : 5.84 5.28 5.28 5.21 5.39 5.66 5.46 5.13 5.75 5.28',
                    'google.com : 5.31 6.62 4.96 6.45 5.14 5.16 5.05 5.24 6.04 4.84',
                    '32.32.32.32 : - - - - - - - - - -',
                    '2a02:2398:106::1 : - - - - - - - - - - - - - - -',
                ],
                [
                    [
                        'ip' => '8.8.8.8',
                        'result' => ['5.84', '5.28', '5.28', '5.21', '5.39', '5.66', '5.46', '5.13', '5.75', '5.28'],
                    ],
                    [
                        'ip' => 'google.com',
                        'result' => ['5.31', '6.62', '4.96', '6.45', '5.14', '5.16', '5.05', '5.24', '6.04', '4.84'],
                    ],
                    [
                        'ip' => '32.32.32.32',
                        'result' => ['-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1'],
                    ],
                    [
                        'ip' => '2a02:2398:106::1',
                        'result' => ['-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1'],
                    ],
                ],
            ],
        ];
    }
}
