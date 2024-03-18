<?php
declare(strict_types=1);

namespace App\Tests\Slave\Task;

use App\Slave\Task\PublishStatistics;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class PublishStatisticsTest extends TestCase
{
    public function testSetArgsDefaults(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client());
        $class->setArgs([]);

        self::assertSame('POST', $class->getMethod());
        self::assertSame('/api/slaves/slave/stats', $class->getEndpoint());
        self::assertInstanceOf(\stdClass::class, $class->getBody());
    }

    public function testSetArguments(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client());
        $class->setArgs([
            'method' => 'GET',
            'body' => ['foo' => 'bar']
        ]);

        self::assertSame('GET', $class->getMethod());
        self::assertSame(['foo' => 'bar'], $class->getBody());
    }

    public function testGetType(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client());

        self::assertSame(PublishStatistics::class, $class->getType());
    }

    public function testHandlesClientException(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(400, [], Utils::streamFor('You did a bad thing'))
            ])
        ]));
        $class->setArgs([]);

        $expected = [
            'code' => 400,
            'contents' => "Client error: `POST /api/slaves/slave/stats` resulted in a `400 Bad Request` response:\nYou did a bad thing\n"
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }

    public function testHandlesServerException(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(500, [], Utils::streamFor('I did a bad thing'))
            ])
        ]));
        $class->setArgs([]);

        $expected = [
            'code' => 500,
            'contents' => "Server error: `POST /api/slaves/slave/stats` resulted in a `500 Internal Server Error` response:\nI did a bad thing\n"
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }

    public function testHandlesConnectException(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client([
            'handler' => MockHandler::createWithMiddleware([
                new ConnectException('Failure!', new Request('GET', '/api/slaves'))
            ])
        ]));
        $class->setArgs([]);

        $expected = [
            'code' => 0,
            'contents' => 'Failure!'
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }

    public function testExecute(): void
    {
        $class = new PublishStatistics(new TestLogger(), new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(200, [], Utils::streamFor('there is a result here!'))
            ])
        ]));
        $class->setArgs([]);

        $expected = [
            'code' => 200,
            'contents' => 'there is a result here!'
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }
}
