<?php
declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\AlertDestination;

use App\Model\Parameter\AlertDestination\HttpParameters;
use PHPUnit\Framework\TestCase;

class HttpParametersTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = HttpParameters::fromArray([
            'url' => 'https://webhook.example'
        ]);

        self::assertInstanceOf(HttpParameters::class, $arguments);
        self::assertSame(['url' => 'https://webhook.example'], $arguments->asArray());
    }

}