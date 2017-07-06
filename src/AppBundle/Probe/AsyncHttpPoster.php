<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 6/07/2017
 * Time: 10:15
 */

namespace AppBundle\Probe;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class AsyncHttpPoster extends Poster
{
    public function __construct($target = null)
    {
        if (is_null($target)) {
            throw new \Exception("Please specify a target.");
        }

        parent::__construct($target);
    }

    public function post(Message $message)
    {
        $client = new Client();
        $promise = $client->postAsync('POST', $this->target);
        $promise->then(
            function (ResponseInterface $response) {
                return $response->getBody();
            }
        );
        $promise->wait();
    }
}