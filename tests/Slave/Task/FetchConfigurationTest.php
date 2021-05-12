<?php
declare(strict_types=1);

namespace App\Tests\Slave\Task;

use App\Slave\Client\FirepingClient;
use App\Slave\Task\FetchConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FetchConfigurationTest extends TestCase
{
    public function testGetType(): void
    {
        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([]));
        self::assertEquals(FetchConfiguration::class, $task->getType());
    }

    public function testSetArgs(): void
    {
        $mock = new MockHandler([]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'handler' => $mock
        ]));

        $task->setArgs(['etag' => 'C0FF33']);
        self::assertEquals('C0FF33', $task->getEtag());
    }

    public function testEtagIsNullByDefault(): void
    {
        $mock = new MockHandler([]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'handler' => $mock
        ]));

        $task->setArgs([]);
        self::assertNull($task->getEtag());
    }

    public function testExecuteUnchangedConfiguration(): void
    {
        $mock = new MockHandler([
            new Response(304, [
                'ETag' => 'C0FF33v2'
            ])
        ]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'handler' => $mock
        ]));

        $task->setArgs(['etag' => 'C0FF33']);
        self::assertEqualsCanonicalizing(['code' => 304, 'contents' => 'Configuration unchanged', 'etag' => 'C0FF33v2'], $task->execute());
    }

    public function testExecuteBrokenJson(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{')
        ]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'handler' => $mock
        ]));

        $task->setArgs(['etag' => 'C0FF33']);
        self::assertEqualsCanonicalizing(['code' => 500, 'contents' => 'Malformed JSON', 'etag' => null], $task->execute());
    }

    public function testExecuteSuccessful(): void
    {
        $mock = new MockHandler([
            new Response(200, ['ETag' => 'C0FF33v2'], '{}')
        ]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'handler' => $mock
        ]));

        $task->setArgs(['etag' => 'C0FF33']);
        self::assertEqualsCanonicalizing(['code' => 200, 'contents' => [], 'etag' => 'C0FF33v2'], $task->execute());
    }

    public function testExecuteHandlesClientErrors(): void
    {
        $mock = new MockHandler([
            new RequestException('An error occurred', new Request('GET', 'test'), new Response(400))
        ]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'http_errors' => true,
            'handler' => HandlerStack::create($mock)
        ]));

        self::assertEqualsCanonicalizing(['code' => 500, 'contents' => 'An error occurred', 'etag' => null], $task->execute());
    }

    public function testExecuteHandlesServerErrors(): void
    {
        $mock = new MockHandler([
            new RequestException('An error occurred', new Request('GET', 'test'), new Response(500))
        ]);

        $task = new FetchConfiguration(new NullLogger(), new FirepingClient([
            'http_errors' => true,
            'handler' => HandlerStack::create($mock)
        ]));

        self::assertEqualsCanonicalizing(['code' => 500, 'contents' => 'An error occurred', 'etag' => null], $task->execute());
    }
}
