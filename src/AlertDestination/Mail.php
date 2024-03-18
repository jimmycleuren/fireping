<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Twig\Environment;
use UnexpectedValueException;
use function array_key_exists;
use function filter_var;
use const FILTER_VALIDATE_EMAIL;

class Mail extends AlertDestinationInterface
{
    private Swift_Mailer $mailer;
    private Environment $twig;
    private LoggerInterface $logger;

    private string $from;
    private string $recipient;

    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, Environment $twig, string $from)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;

        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            throw new UnexpectedValueException('invalid e-mail address');
        }

        $this->from = $from;
    }

    public function setParameters(array $parameters): void
    {
        if (array_key_exists('recipient', $parameters) === false) {
            throw new UnexpectedValueException('mail requires recipient to be set');
        }

        if (!is_string($parameters['recipient'])) {
            throw new InvalidArgumentException('recipient must be a string');
        }

        if (filter_var($parameters['recipient'], FILTER_VALIDATE_EMAIL) === false) {
            throw new UnexpectedValueException('invalid recipient e-mail address');
        }

        $this->recipient = $parameters['recipient'];
    }

    public function trigger(Alert $alert): void
    {
        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'ALERT');
    }

    private function sendMail(string $to, string $subject, Alert $alert, string $action): void
    {
        try {
            $message = (new \Swift_Message($subject))
                ->setFrom($this->from)
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

            if (!$this->mailer->send($message)) {
                $this->logger->warning("Mail to $to could not be sent");
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function clear(Alert $alert): void
    {
        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'CLEAR');
    }
}
