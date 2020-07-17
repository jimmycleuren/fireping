<?php
declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\AlertDestination;

use App\Model\Parameter\AlertDestination\MailParameters;
use PHPUnit\Framework\TestCase;

class MailParametersTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = MailParameters::fromArray(['recipient' => 'test@email.me']);

        self::assertInstanceOf(MailParameters::class, $arguments);
        self::assertSame(['recipient' => 'test@email.me'], $arguments->asArray());
    }
}
