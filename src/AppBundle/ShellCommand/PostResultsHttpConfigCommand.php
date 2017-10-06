<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 6/10/2017
 * Time: 13:11
 */

namespace AppBundle\ShellCommand;

use AppBundle\ShellCommand\CommandInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\TransferException;

class PostResultsHttpConfigCommand implements CommandInterface
{
    /* @var $arguments array */
    protected $arguments = [];

    /* @var $container \Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /* @var $logger \Psr\Log\LoggerInterface */
    protected $logger;

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

    function __construct($args)
    {
        $this->arguments = $args;
        $this->container = $args['container'];
        $this->logger    = $this->container->get('logger');

        if (isset($args['client'])) {
            $this->client = $this->container->get($args['client']);
        } elseif (isset($args['uri'])) {
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => $args['uri'],
            ]);
        }

        $this->method   = $args['method'];
        $this->endpoint = $args['endpoint'];
        $this->headers  = isset($args['headers']) ? $args['headers'] : [];
        $this->body     = isset($args['body']) ? $args['body'] : null;
    }

    public function execute()
    {
        try {
            $request = new Request($this->method, $this->endpoint, $this->headers, $this->body);
            $response = $this->client->send($request);

            return array(
                'code' => $response->getStatusCode(),
                'endpoint' => $this->endpoint,
                'contents' => $response->getBody()
            );

        } catch (TransferException $e) {
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