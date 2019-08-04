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
use Twig\Environment;

/**
 * Class Mail
 * @package App\AlertDestination
 */
class Mail extends AlertDestinationInterface
{
    protected $recipient;
    protected $mailer;
    protected $twig;
    protected $logger;

    /**
     * @param \Swift_Mailer $mailer
     * @param LoggerInterface $logger
     * @param Environment $twig
     */
    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
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
                    $this->twig->render(
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