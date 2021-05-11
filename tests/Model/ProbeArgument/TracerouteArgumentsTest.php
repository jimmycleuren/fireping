<?php

declare(strict_types=1);

namespace App\Tests\Model\ProbeArgument;

use App\Model\ProbeArgument\NullArguments;
use PHPUnit\Framework\TestCase;

class TracerouteArgumentsTest extends TestCase
{
    public function testDefault()
    {
        $arguments = NullArguments::fromJsonString('{}');
        self::assertSame([], $arguments->asArray());
    }
}
