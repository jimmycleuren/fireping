<?php

namespace App\AlertDestination;

use App\Entity\Alert;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * Class Mail.
 */
class Mail extends AlertDestinationInterface
{
    protected $recipient;
    protected $mailer;
    protected $twig;
    protected $logger;

    /**
     * Mail constructor.
     */
    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
    }

    public function setParameters(array $parameters)
    {
        if ($parameters) {
            $this->recipient = $parameters['recipient'];
        }
    }

    public function trigger(Alert $alert)
    {
        if (!isset($_ENV['MAILER_FROM'])) {
            $this->logger->error('MAILER_FROM env variable is not set');

            return;
        }

        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'ALERT');
    }

    public function clear(Alert $alert)
    {
        if (!isset($_ENV['MAILER_FROM'])) {
            $this->logger->error('MAILER_FROM env variable is not set');

            return;
        }

        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'CLEAR');
    }

    private function sendMail(string $to, string $subject, Alert $alert, string $action)
    {
        try {
            $message = (new \Swift_Message($subject))
                ->setFrom($_ENV['MAILER_FROM'])
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
}
