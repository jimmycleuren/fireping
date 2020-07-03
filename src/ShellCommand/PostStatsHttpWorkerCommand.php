<?php

declare(strict_types=1);

namespace App\ShellCommand;

use App\Client\FirepingClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Psr\Log\LoggerInterface;
use stdClass;

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

    public function __construct(LoggerInterface $logger, FirepingClient $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function execute(): array
    {
        $startedAt = microtime(true);
        $this->logger->info('worker: attempting to publish stats to master');
        try {
            $response = $this->client->request($this->method, $this->endpoint, ['json' => $this->body]);

            try {
                $out = json_encode($this->body, JSON_THROW_ON_ERROR, 512);
                $this->logger->debug(sprintf('worker: stats: %s', $out));
            } catch (JsonException $exception) {
                $this->logger->error(sprintf('failed to encode statistics as json: %s', $exception->getMessage()));
                $this->logger->debug(sprintf('worker: stats: %s', serialize($this->body)));
            }

            $this->logger->info(sprintf('worker: published stats (took %.2f seconds)', microtime(true) - $startedAt));
            return ['code' => $response->getStatusCode(), 'contents' => (string) $response->getBody()];
        } catch (RequestException $exception) {
            $this->logger->error(sprintf('worker: failed to publish stats: %s (took %.2f seconds)', $exception->getMessage(), microtime(true) - $startedAt));

            $body = $exception->getResponse() === null ? 'empty body' : (string) $exception->getResponse()->getBody();
            $this->logger->debug(sprintf('worker: stats response body: %s (took %.2f seconds)', $body, microtime(true) - $startedAt));

            return ['code' => $exception->getCode(), 'contents' => $exception->getMessage()];
        } catch (GuzzleException $exception) {
            $this->logger->error(sprintf('worker: failed to publish stats: %s (took %.2f seconds)', $exception->getMessage(), microtime(true) - $startedAt));

            return ['code' => $exception->getCode(), 'contents' => $exception->getMessage()];
        }
    }

    public function setArgs(array $args): void
    {
        $this->method = $args['method'] ?? 'POST';
        $this->endpoint = sprintf('/api/slaves/%s/stats', $_ENV['SLAVE_NAME']);
        $this->body = $args['body'] ?? new stdClass();
    }

    public function getType(): string
    {
        return self::class;
    }
}
