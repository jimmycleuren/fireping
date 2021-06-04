<?php
declare(strict_types=1);

namespace App\Tests\Slave\Command;

use App\Slave\Command\HealthCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class HealthCommandTest extends TestCase
{
    public function testHealthy(): void
    {
        $command = new HealthCommand(new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(200)
            ])
        ]));

        $tester = new CommandTester($command);
        $rc = $tester->execute([]);

        self::assertSame(0, $rc);
        self::assertSame('healthy', trim($tester->getDisplay()));
    }

    public function testNotHealthy(): void
    {
        $command = new HealthCommand(new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(500)
            ])
        ]));

        $tester = new CommandTester($command);
        $rc = $tester->execute([]);

        self::assertSame(1, $rc);
        self::assertSame('unhealthy', trim($tester->getDisplay()));
    }
}
