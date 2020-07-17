<?php

declare(strict_types=1);

namespace App\Tests\App\Model\Parameter;

use App\Model\Parameter\NullParameters;
use PHPUnit\Framework\TestCase;

class NullParametersTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = NullParameters::fromArray([]);
        self::assertSame([], $arguments->asArray());
    }
}