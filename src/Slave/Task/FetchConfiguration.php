<?php

declare(strict_types=1);

namespace App\Slave\Task;

use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class FetchConfiguration implements TaskInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string|null
     */
    protected $etag;
    private ClientInterface $client;

    public function __construct(LoggerInterface $logger, ClientInterface $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function execute(): array
    {
        $endpoint = sprintf('/api/slaves/%s/config', $_ENV['SLAVE_NAME']);

        try {
            $response = $this->client->request('GET', $endpoint, [
                RequestOptions::HEADERS => \array_filter([
                    'If-None-Match' => $this->etag
                ]),
                RequestOptions::COOKIES => new FileCookieJar(sys_get_temp_dir().'/.slave.cookiejar.json', true)
            ]);

            $eTag = $response->getHeader('ETag')[0] ?? null;

            if (304 === $response->getStatusCode()) {
                return ['code' => 304, 'contents' => 'Configuration unchanged', 'etag' => $eTag];
            }

            $configuration = json_decode((string)$response->getBody(), true);
            if (null === $configuration) {
                return ['code' => 500, 'contents' => 'Malformed JSON', 'etag' => $eTag];
            }

            return ['code' => 200, 'contents' => $configuration, 'etag' => $eTag];
        } catch (GuzzleException $exception) {
            return ['code' => 500, 'contents' => $exception->getMessage(), 'etag' => null];
        }
    }

    public function setArgs(array $args): void
    {
        $this->etag = $args['etag'] ?? null;
    }

    public function getType(): string
    {
        return self::class;
    }
}
