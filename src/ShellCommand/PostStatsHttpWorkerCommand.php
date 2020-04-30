<?php

declare(strict_types=1);

namespace App\ShellCommand;

use App\Client\FirepingClient;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;

class PostStatsHttpWorkerCommand implements CommandInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var FirepingClient
     */
    private $client;
    private $method;
    private $endpoint;
    private $body;

    function __construct(LoggerInterface $logger, FirepingClient $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function execute(): array
    {
        try {
            $response = $this->client->request($this->method, $this->endpoint, ['json' => $this->body]);
            return ['code' => $response->getStatusCode(), 'contents' => (string)$response->getBody()];
        } catch (GuzzleException $exception) {
            return ['code' => $exception->getCode(), 'contents' => $exception->getMessage()];
        }
    }

    public function setArgs(array $args): void
    {
        $this->method = $args['method'] ?? 'POST';
        $this->endpoint = sprintf('/api/slaves/%s/stats', $_ENV['SLAVE_NAME']);
        $this->body = $args['body'] ?? new \stdClass();
    }

    public function getType(): string
    {
        return self::class;
    }
}