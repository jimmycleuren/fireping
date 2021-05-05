<?php

declare(strict_types=1);

namespace App\Slave\Task;

use App\Slave\Client\FirepingClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
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
    /**
     * @var FirepingClient
     */
    private $client;

    public function __construct(LoggerInterface $logger, FirepingClient $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function execute(): array
    {
        $endpoint = sprintf('/api/slaves/%s/config', $_ENV['SLAVE_NAME']);

        $headers = null !== $this->etag ? ['If-None-Match' => $this->etag] : [];
        $request = new Request('GET', $endpoint, $headers);
        try {
            $response = $this->client->send($request);
            $eTag = $response->getHeader('ETag')[0] ?? null;

            if (304 === $response->getStatusCode()) {
                return ['code' => 304, 'contents' => 'Configuration unchanged', 'etag' => $eTag];
            }

            $configuration = json_decode((string) $response->getBody(), true);
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
