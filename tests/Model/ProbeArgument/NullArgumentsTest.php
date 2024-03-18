<?php

declare(strict_types=1);

namespace App\Tests\Model\ProbeArgument;

use App\Model\ProbeArgument\NullArguments;
use PHPUnit\Framework\TestCase;

class NullArgumentsTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = NullArguments::fromJsonString('{}');
        self::assertSame([], $arguments->asArray());
    }
}
