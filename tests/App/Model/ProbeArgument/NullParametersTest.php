<?php

declare(strict_types=1);

namespace App\Tests\App\Model\ProbeArgument;

use App\Model\ProbeArgument\NullParameters;
use PHPUnit\Framework\TestCase;

class NullParametersTest extends TestCase
{
    public function testDefault()
    {
        $arguments = NullParameters::fromJsonString('{}');
        self::assertSame([], $arguments->asArray());
    }
}