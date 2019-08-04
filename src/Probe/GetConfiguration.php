<?php
declare(strict_types=1);

namespace App\Probe;

use App\Client\FirepingClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

class GetConfiguration implements CommandInterface
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

    function __construct(LoggerInterface $logger, FirepingClient $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function execute(): array
    {
        $endpoint = sprintf('/api/slaves/%s/config', $_ENV['SLAVE_NAME']);

        $headers = $this->etag !== null ? ['If-None-Match' => $this->etag] : [];
        $request = new Request('GET', $endpoint, $headers);
        try {
            $response = $this->client->send($request);
            $eTag = $response->getHeader('ETag')[0] ?? null;

            if ($response->getStatusCode() === 304) {
                return ['code' => 304, 'contents' => 'Configuration unchanged', 'etag' => $eTag];
            }

            $configuration = json_decode((string)$response->getBody(), true);
            if ($configuration === null) {
                return ['code' => 500, 'contents' => 'Malformed JSON', 'etag' => $eTag];
            }

            if (empty($configuration)) {
                return ['code' => 201, 'contents' => 'Configuration empty', 'etag' => $eTag];
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