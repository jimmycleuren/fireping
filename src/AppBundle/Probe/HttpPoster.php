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

    public function postExperiment(Message $message)
    {
        $client = new Client();
        try {
            $response = $client->post($this->target, array(
                'http_errors' => true,
                'json' => json_encode($message->getBody()),
            ));
            return $response->getBody();
        } catch (ClientException $exception) {
            // Return|Throw -> Indicate Message SHOULD NOT be retried.
            throw new DiscardMessageException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception);
        } catch (ConnectException $exception) {
            // Return|Throw -> Indicate Message SHOULD be retried
            throw new RetryMessageException($exception->getMessage());
        } catch (\Exception $exception) {
            // Return|Throw -> Indicate an Exception that was not specifically handled.
            // These SHOULD be discarded just to be safe (prevent queue blocking)
            // This SHOULD raise an incident so we can investigate the exception,
            //   and handle it specifically.
            throw new UnhandledMessageException($exception->getMessage());
        }
    }
}