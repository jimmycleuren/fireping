<?php

namespace App\Slave\Exception;

use Throwable;

class WorkerTimedOutException extends \Exception
{
    public function __construct(private $timeout, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}
