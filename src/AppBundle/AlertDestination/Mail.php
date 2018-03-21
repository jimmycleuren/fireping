<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 19:46
 */

namespace AppBundle\AlertDestination;

use AppBundle\Entity\Alert;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;

class Mail extends AlertDestinationInterface
{
    protected $recipient;
    protected $mailer;

    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, TwigEngine $templating)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->templating = $templating;
    }

    public function setParameters($parameters)
    {
        if ($parameters) {
            $this->recipient = $parameters['recipient'];
        }
    }

    public function trigger(Alert $alert)
    {
        if (!getenv('MAILER_FROM')) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $alertRule = $alert->getAlertRule();
        $device = $alert->getDevice()->getName();
        $group = $alert->getSlaveGroup()->getName();

        $this->sendMail($this->recipient, "ALERT: " . $alertRule->getName() . " on $device from $group", $alert);
    }

    public function clear(Alert $alert)
    {
        if (!getenv('MAILER_FROM')) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $alertRule = $alert->getAlertRule();
        $device = $alert->getDevice()->getName();
        $group = $alert->getSlaveGroup()->getName();

        $this->sendMail($this->recipient, "CLEAR: " . $alertRule->getName() . " on $device from $group", $alert);
    }

    private function sendMail($to, $subject, $alert)
    {
        $message = (new \Swift_Message($subject))
            ->setFrom(getenv('MAILER_FROM'))
            ->setTo($to)
            ->setBody(
                $this->templating->render(
                    'emails/alert.html.twig',
                    array('alert' => $alert)
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }
}