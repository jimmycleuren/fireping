<?php
declare(strict_types=1);

namespace App\Tests\Slave\Task;

use App\Slave\Task\FetchConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;

class FetchConfigurationTest extends TestCase
{

    public function testSetEmptyArgs(): void
    {
        $class = new FetchConfiguration(new NullLogger(), new Client());
        $class->setArgs([]);

        self::assertNull($class->getEtag());
    }

    public function testSetRandomArgs(): void
    {
        $class = new FetchConfiguration(new NullLogger(), new Client());
        $class->setArgs(['foo' => 'bar']);

        self::assertNull($class->getEtag());
    }

    public function setEtagArgs(): void
    {
        $class = new FetchConfiguration(new NullLogger(), new Client());
        $class->setArgs(['etag' => 'foo']);

        self::assertSame('foo', $class->getEtag());
    }

    public function testGetType(): void
    {
        $class = new FetchConfiguration(new NullLogger(), new Client());
        self::assertSame(FetchConfiguration::class, $class->getType());
    }

    public function testConfigurationNoChange(): void
    {
        $logger = new TestLogger();
        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(304, [
                    'ETag' => ['foo']
                ])
            ])
        ]);

        $class = new FetchConfiguration($logger, $client);
        $response = $class->execute();

        $expected = [
            'code' => 304,
            'contents' => 'Configuration unchanged',
            'etag' => 'foo'
        ];
        self::assertEqualsCanonicalizing($expected, $response);
    }

    public function testConfigurationNoChangeNoEtag(): void
    {
        $logger = new TestLogger();
        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(304)
            ])
        ]);

        $class = new FetchConfiguration($logger, $client);
        $response = $class->execute();

        $expected = [
            'code' => 304,
            'contents' => 'Configuration unchanged',
            'etag' => null
        ];
        self::assertEqualsCanonicalizing($expected, $response);
    }

    public function testConfigurationInvalidJson(): void
    {
        $logger = new TestLogger();
        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(200, [], Utils::streamFor('{'))
            ])
        ]);

        $class = new FetchConfiguration($logger, $client);
        $response = $class->execute();

        $expected = [
            'code' => 500,
            'contents' => 'Malformed JSON',
            'etag' => null
        ];
        self::assertEqualsCanonicalizing($expected, $response);
    }

    public function testSuccess(): void
    {
        $logger = new TestLogger();
        $configuration = ['some' => 'configuration', 'foo' => [0, 1, 2, 3]];
        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(200, ['ETag' => 'foo'], Utils::streamFor(json_encode($configuration)))
            ])
        ]);

        $class = new FetchConfiguration($logger, $client);
        $response = $class->execute();

        $expected = [
            'code' => 200,
            'contents' => $configuration,
            'etag' => 'foo'
        ];
        self::assertEqualsCanonicalizing($expected, $response);
    }

    public function testGuzzleException(): void
    {
        $logger = new TestLogger();
        $client = new Client([
            'handler' => MockHandler::createWithMiddleware([
                new Response(500)
            ])
        ]);

        $class = new FetchConfiguration($logger, $client);
        $response = $class->execute();

        $expected = [
            'code' => 500,
            'contents' => 'Server error: `GET /api/slaves/slave/config` resulted in a `500 Internal Server Error` response',
            'etag' => null
        ];
        self::assertEqualsCanonicalizing($expected, $response);
    }
}
