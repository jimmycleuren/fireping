<?php

declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\Probe;

use App\Model\Parameter\NullParameters;
use App\Model\Parameter\Probe\TracerouteParameters;
use PHPUnit\Framework\TestCase;

class TracerouteParametersTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = TracerouteParameters::fromArray([]);

        self::assertInstanceOf(TracerouteParameters::class, $arguments);
        self::assertSame([], $arguments->asArray());
    }
}