<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class Mail extends AlertDestinationHandler
{
    /**
     * @var string
     */
    protected $recipient;
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;
    /**
     * @var Environment
     */
    protected $templating;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, Environment $templating)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->templating = $templating;
    }

    public function setParameters(array $parameters): void
    {
        if ($parameters) {
            $this->recipient = (string) $parameters['recipient'];
        }
    }

    public function trigger(Alert $alert): void
    {
        if (!isset($_ENV['MAILER_FROM'])) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'ALERT');
    }

    private function sendMail(string $to, string $subject, Alert $alert, string $action): void
    {
        try {
            $message = (new \Swift_Message($subject))
                ->setFrom($_ENV['MAILER_FROM'])
                ->setTo($to)
                ->setBody(
                    $this->templating->render(
                        'emails/alert.html.twig',
                        array(
                            'alert' => $alert,
                            'action' => $action
                        )
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
        if (!isset($_ENV['MAILER_FROM'])) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, "CLEAR");
    }
}