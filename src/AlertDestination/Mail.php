<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 19:46
 */

namespace App\AlertDestination;

use App\Entity\Alert;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;

/**
 * Class Mail
 * @package App\AlertDestination
 */
class Mail extends AlertDestinationInterface
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
        if (!getenv('MAILER_FROM')) {
            $this->logger->error('MAILER_FROM env variable is not set');
            return;
        }

        $alertRule = $alert->getAlertRule();
        $device = $alert->getDevice()->getName();
        $group = $alert->getSlaveGroup()->getName();

        $this->sendMail($this->recipient, "ALERT: " . $alertRule->getName() . " on $device from $group", $alert);
    }

    /**
     * @param Alert $alert
     */
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

    /**
     * @param string $to
     * @param string $subject
     * @param Alert $alert
     * @throws \Twig\Error\Error
     */
    private function sendMail(string $to, string $subject, Alert $alert)
    {
        try {
            $message = (new \Swift_Message($subject))
                ->setFrom(getenv('MAILER_FROM'))
                ->setTo($to)
                ->setBody(
                    $this->templating->render(
                        'emails/alert.html.twig',
                        array('alert' => $alert)
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