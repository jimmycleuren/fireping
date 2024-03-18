<?php

declare(strict_types=1);

namespace App\Tests\Common\Version;

use App\Common\Process\DummyProcessFactory;
use App\Common\Process\ProcessFixture;
use App\Common\Version\GitVersionReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GitVersionReaderTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testReadingCreatesVersionObjects(ProcessFixture $fixture, string $version): void
    {
        $factory = new DummyProcessFactory();
        $factory->addFixture(sha1(serialize(['git', 'describe', '--always'])), $fixture);
        $reader = new GitVersionReader(new NullLogger(), $factory);
        self::assertEquals($version, $reader->version()->asString());
    }

    public function dataProvider()
    {
        return [
            [new ProcessFixture('', '', true), ''],
            [new ProcessFixture('e240044', '', true), 'e240044'],
            [new ProcessFixture('v1.0', '', true), 'v1.0'],
            [new ProcessFixture('', 'fatal: No names found, cannot describe anything.', false), ''],
        ];
    }
}