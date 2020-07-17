<?php
declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\AlertDestination;

use App\Model\Parameter\AlertDestination\MonologParameters;
use PHPUnit\Framework\TestCase;

class MonologParametersTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = MonologParameters::fromArray([]);

        self::assertInstanceOf(MonologParameters::class, $arguments);
        self::assertSame([], $arguments->asArray());
    }
}