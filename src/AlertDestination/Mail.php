<?php

declare(strict_types=1);

namespace App\AlertDestination;

use App\Entity\Alert;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;

/**
 * Class Mail
 * @package App\AlertDestination
 */
class Mail extends AlertDestinationHandler
{
    protected $recipient;
    protected $mailer;
    protected $templating;
    protected $logger;

    /**
     * Mail constructor.
     * @param \Swift_Mailer $mailer
     * @param LoggerInterface $logger
     * @param TwigEngine $templating
     */
    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, TwigEngine $templating)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->templating = $templating;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        if ($parameters) {
            $this->recipient = $parameters['recipient'];
        }
    }

    /**
     * @param Alert $alert
     */
    public function trigger(Alert $alert)
    {
        if (!isset($_ENV['MAILER_FROM'])) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, 'ALERT');
    }

    /**
     * @param Alert $alert
     */
    public function clear(Alert $alert)
    {
        if (!isset($_ENV['MAILER_FROM'])) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $this->sendMail($this->recipient, $this->getAlertMessage($alert), $alert, "CLEAR");
    }

    /**
     * @param string $to
     * @param string $subject
     * @param Alert $alert
     * @param string $action
     */
    private function sendMail(string $to, string $subject, Alert $alert, string $action)
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
}