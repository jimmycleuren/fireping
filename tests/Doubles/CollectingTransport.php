<?php

declare(strict_types=1);

namespace App\Tests\Doubles;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class CollectingTransport implements TransportInterface
{
    private array $messages = [];

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $sentMessage = new SentMessage($message, $envelope ?? Envelope::create($message));
        $this->messages[] = $sentMessage;
        return $sentMessage;
    }

    public function __toString(): string
    {
        return 'collecting://';
    }

    /**
     * @return SentMessage[]
     */
    public function getSentMessages(): array
    {
        return $this->messages;
    }
}
