<?php
declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use App\Exception\ClearException;
use App\Exception\TriggerException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Mail extends AlertDestinationInterface
{
    private string $recipient;
    private Swift_Mailer $mailer;
    private Environment $twig;
    private LoggerInterface $logger;
    private string $sender;

    public function __construct(Swift_Mailer $mailer, LoggerInterface $logger, Environment $twig, string $sender)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->sender = $sender;
    }

    public function setParameters(array $parameters)
    {
        if ($parameters) {
            $this->recipient = $parameters['recipient'];
        }
    }

    public function trigger(Alert $alert)
    {
        if ($this->sender === '') {
            throw new TriggerException('Sender is missing.');
        }

        try {
            if ($this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'ALERT') === 0) {
                throw new TriggerException('Failed to mail trigger event to ' . $this->recipient);
            }
        } catch (Error $e) {
            throw new TriggerException($e->getMessage(), 0, $e);
        }
    }

    public function clear(Alert $alert)
    {
        if ($this->sender === '') {
            throw new ClearException('Sender is missing.');
        }

        try {
            if ($this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'CLEAR') === 0) {
                throw new ClearException('Failed to mail clear event to ' . $this->recipient);
            }
        } catch (Error $e) {
            throw new ClearException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendMail(string $to, string $subject, Alert $alert, string $action): int
    {
        $message = (new Swift_Message($subject))
            ->setFrom($this->sender)
            ->setTo($to)
            ->setBody(
                $this->twig->render(
                    'emails/alert.html.twig',
                    [
                        'alert' => $alert,
                        'action' => $action,
                    ]
                ),
                'text/html'
            );

        return $this->mailer->send($message);
    }
}
