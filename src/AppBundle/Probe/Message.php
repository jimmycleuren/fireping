<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 13:13
 */

namespace AppBundle\Probe;


class Message
{
    protected $code;
    protected $subject;
    protected $body;

    const MESSAGE_OK = 200;

    const CLIENT_ERROR = 400;
    const NOT_FOUND = 404;

    const SERVER_ERROR = 500;

    const E_REJECT_UNHANDLED = 1;
    const E_REJECT_RETRY = 2;
    const E_REJECT_RETRY_PRIORITY = 3;
    const E_REJECT_DISCARD = 4;
    const E_REJECT_ABORT = 5;

    public static $codeMap = array(
        Message::MESSAGE_OK => 'OK',
        Message::CLIENT_ERROR => 'Client Error.',
        Message::NOT_FOUND => 'Not found.',
        Message::SERVER_ERROR => 'Server Error.',
    );

    public function __construct(int $code, string $subject, array $body)
    {
        if (!isset(Message::$codeMap[$code])) {
            throw new \Exception("Illegal status code.");
        }

        if (empty($body)) {
            throw new \Exception("Body should not be empty.");
        }

        $this->code = $code;
        $this->subject = empty($subject) ? Message::$codeMap[$code] : $subject;
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function asArray()
    {
        return array(
            'code' => $this->code,
            'subject' => $this->subject,
            'body' => $this->body,
        );
    }

    public function __toString()
    {
        return json_encode($this->asArray());
    }
}