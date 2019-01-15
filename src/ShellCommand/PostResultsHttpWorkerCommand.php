<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 6/10/2017
 * Time: 13:11
 */

namespace App\ShellCommand;

use App\ShellCommand\CommandInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\TransferException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PostResultsHttpWorkerCommand implements CommandInterface
{
    /* @var $arguments array */
    protected $arguments = [];

    /* @var $container \Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /* @var $client \GuzzleHttp\Client */
    protected $client;

    /* @var $uri string */
    protected $uri;

    /* @var $method string */
    protected $method;

    /* @var $endpoint string */
    protected $endpoint;

    /* @var $headers array */
    protected $headers;

    /* @var $body string */
    protected $body;

    protected $logger;

    function __construct($args, LoggerInterface $logger, ContainerInterface $container)
    {
        $this->arguments = $args;
        $this->logger = $logger;
        $this->container = $container;

        if (isset($args['client'])) {
            $this->client = $this->container->get($args['client']);
        } elseif (isset($args['uri'])) {
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => $args['uri'],
                'cookies' => true
            ]);
        }

        $this->method   = $args['method'];
        $this->endpoint = $args['endpoint'];
        $this->headers  = isset($args['headers']) ? array_change_key_case($args['headers']) : [];

        if ($this->isJsonRequest($this->headers)) {
            $this->body = isset($args['body']) ? json_encode($args['body']) : "{}";
        } else {
            $this->body = isset($args['body']) ? $args['body'] : "";
        }
    }

    private function isJsonRequest($headers)
    {
        if (in_array('content-type', array_keys($this->headers))) {
            if (strtolower($this->headers['content-type']) === 'application/json') {
                return true;
            }
        }
        return false;
    }

    public function execute()
    {
        try {
            $request = new Request($this->method, $this->endpoint, $this->headers, $this->body);
            $response = $this->client->send($request);

            return array(
                'code' => $response->getStatusCode(),
                'endpoint' => $this->endpoint,
                'contents' => $response->getBody()->getContents(),
            );

        } catch (TransferException $e) {
            return array(
                'code' => $e->getCode(),
                'endpoint' => $this->endpoint,
                'contents' => $e->getMessage(),
            );
        } catch (\Exception $e) {
            return array(
                'code' => $e->getCode(),
                'endpoint' => $this->endpoint,
                'contents' => $e->getMessage(),
            );
        }
    }

    function build()
    {
        // TODO: Implement build() method.
    }

    function valid()
    {
        // TODO: Implement valid() method.
    }
}