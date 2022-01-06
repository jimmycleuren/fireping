<?php
declare(strict_types=1);

namespace App\Tests\Doubles;

use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

class MockTransport implements Swift_Transport
{
    private int $sent = 0;

    public function isStarted(): bool
    {
        return true;
    }

    public function start()
    {
    }

    public function stop()
    {
    }

    public function ping(): bool
    {
        return true;
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null): int
    {
        $this->sent = count($message->getTo() ?? []) + count($message->getCc() ?? []) + count($message->getBcc() ?? []);
        return $this->sent;
    }

    public function getSent(): int
    {
        return $this->sent;
    }

    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
    }
}
