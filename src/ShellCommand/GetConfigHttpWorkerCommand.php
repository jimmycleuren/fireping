<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 5/10/2017
 * Time: 15:26
 */

namespace App\ShellCommand;

use App\ShellCommand\CommandInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\TransferException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GetConfigHttpWorkerCommand implements CommandInterface
{
    /* @var $container \Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /* @var $logger \Psr\Log\LoggerInterface */
    protected $logger;

    protected $etag;

    protected $arguments;

    function __construct($args, LoggerInterface $logger, ContainerInterface $container)
    {
        $this->arguments = $args;
        $this->logger = $logger;
        $this->container = $container;
        $this->etag      = isset($args['etag']) ? $args['etag'] : null;
    }

    function execute()
    {
        /** @var \GuzzleHttp\Client $client */
        $client = $this->container->get('eight_points_guzzle.client.api_fireping');

        $id       = getenv('SLAVE_NAME');
        $endpoint = "/api/slaves/$id/config";

        try {
            $request = isset($this->etag) ?
                new Request('GET', $endpoint, ['If-None-Match' => $this->etag]) :
                new Request('GET', $endpoint);

            $response = $client->send($request);

            $etag = $response->hasHeader('ETag') ? $response->getHeader('ETag')[0] : null;

            if ($response->getStatusCode() === 304) {
                return array('code' => 304, 'contents' => 'Configuration has not changed.', 'etag' => $etag);
            }

            $configuration = json_decode($response->getBody()->getContents(), true);

            if ($configuration === null) {
                return array('code' => 500, 'contents' => 'Master is returning non-JSON.', 'etag' => $etag);
            }

            if (count($configuration) === 0) {
                return array('code' => 201, 'contents' => 'Empty configuration received.', 'etag' => $etag);
            }

            return array('code' => 200, 'contents' => $configuration, 'etag' => $etag);
        } catch (TransferException $e) {
            return array('code' => 500, 'contents' => $e->getMessage(), 'etag' => null);
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