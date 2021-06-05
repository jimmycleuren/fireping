<?php
declare(strict_types=1);

namespace App\Tests\Slave\Task;

use App\Slave\Task\PublishResults;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class PublishResultsTest extends TestCase
{
    public function testGetType(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware()
        ]);

        $class = new PublishResults($logger, $client);
        self::assertSame(PublishResults::class, $class->getType());
    }

    public function testSetArgsDefaults(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware()
        ]);

        $class = new PublishResults($logger, $client);
        $class->setArgs([]);

        self::assertSame('POST', $class->getMethod());
        self::assertSame('/api/slaves/slave/result', $class->getEndpoint());
        self::assertInstanceOf(\stdClass::class, $class->getBody());
    }

    public function testSetArguments(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware()
        ]);

        $class = new PublishResults($logger, $client);
        $class->setArgs([
            'method' => 'GET',
            'body' => ['foo' => 'bar']
        ]);

        self::assertSame('GET', $class->getMethod());
        self::assertSame(['foo' => 'bar'], $class->getBody());
    }

    public function testHandlesClientException(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(400, [], Utils::streamFor('You did a bad thing'))
            ])
        ]);

        $class = new PublishResults($logger, $client);
        $class->setArgs([]);

        $expected = [
            'code' => 400,
            'contents' => "Client error: `POST /api/slaves/slave/result` resulted in a `400 Bad Request` response:\nYou did a bad thing\n"
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }

    public function testHandlesServerException(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(500, [], Utils::streamFor('I did a bad thing'))
            ])
        ]);

        $class = new PublishResults($logger, $client);
        $class->setArgs([]);

        $expected = [
            'code' => 500,
            'contents' => "Server error: `POST /api/slaves/slave/result` resulted in a `500 Internal Server Error` response:\nI did a bad thing\n"
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }

    public function testHandlesConnectException(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new ConnectException('Failure!', new Request('GET', '/api/slaves'))
            ])
        ]);

        $class = new PublishResults($logger, $client);
        $class->setArgs([]);

        $expected = [
            'code' => 0,
            'contents' => 'Failure!'
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }

    public function testExecute(): void
    {
        $logger = new TestLogger();

        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(200, [], Utils::streamFor('there is a result here!'))
            ])
        ]);

        $class = new PublishResults($logger, $client);
        $class->setArgs([]);

        $expected = [
            'code' => 200,
            'contents' => 'there is a result here!'
        ];

        self::assertEqualsCanonicalizing($expected, $class->execute());
    }
}
