<?php

declare(strict_types=1);

namespace App\Tests\App\Version;

use App\Version\GitVersionReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GitVersionReaderTest extends TestCase
{
    public function testReadingCreatesVersionObjects()
    {
        $reader = new GitVersionReader(new NullLogger());
        $reader->version();
    }
}