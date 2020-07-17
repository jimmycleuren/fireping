<?php
declare(strict_types=1);

namespace App\Tests\App\Model\Parameter\AlertDestination;

use App\Model\Parameter\AlertDestination\SlackParameters;
use PHPUnit\Framework\TestCase;

class SlackParametersTest extends TestCase
{
    public function testDefault(): void
    {
        $arguments = SlackParameters::fromArray([
            'channel' => 'channel',
            'url' => 'https://slack.example'
        ]);

        self::assertInstanceOf(SlackParameters::class, $arguments);
        self::assertSame(['channel' => 'channel', 'url' => 'https://slack.example'], $arguments->asArray());
    }
}