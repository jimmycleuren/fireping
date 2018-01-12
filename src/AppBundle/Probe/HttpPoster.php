<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 15:01
 */

namespace AppBundle\Probe;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

class HttpPoster extends Poster
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
        $response = $client->post($this->target, array(
            'http_errors' => true,
            'json' => json_encode($message->getBody())
        ));
        var_dump($response);
        return $response->getBody();
    }
}