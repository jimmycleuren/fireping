<?php

namespace App\Slave\Exception;

use Throwable;

class WorkerTimedOutException extends \Exception
{
    private $timeout;

    public function __construct($timeout, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->timeout = $timeout;
        parent::__construct($message, $code, $previous);
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}
