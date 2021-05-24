<?php

namespace App\Tests\AlertDestination;

use App\AlertDestination\Mail;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use App\Exception\ClearException;
use App\Exception\TriggerException;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Swift_Mime_SimpleMessage;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class MailTest extends TestCase
{
    public function testTriggerWithoutSenderThrowsTriggerException(): void
    {
        $this->expectException(TriggerException::class);

        $mailer = new Mail($this->createMockSwiftMailer([]), new TestLogger(), new Environment(new ArrayLoader([])), '');
        $mailer->trigger(new Alert());
    }

    private function createMockSwiftMailer(array $counts): \Swift_Mailer
    {
        return new class($counts) extends \Swift_Mailer {
            private array $counts;

            public function __construct(array $counts = [])
            {
                parent::__construct(new \Swift_NullTransport());
                $this->counts = $counts;
            }

            public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
            {
                return array_shift($this->counts);
            }
        };
    }

    public function testClearWithoutSenderThrowsClearException(): void
    {
        $this->expectException(ClearException::class);

        $mailer = new Mail($this->createMockSwiftMailer([]), new TestLogger(), new Environment(new ArrayLoader([])), '');
        $mailer->clear(new Alert());
    }

    public function testFailedToSendTriggerEvent(): void
    {
        $this->expectException(TriggerException::class);
        $this->expectExceptionMessage('Failed to mail trigger event to receiver@fireping.local');

        $mailer = new Mail($this->createMockSwiftMailer([0]), new TestLogger(), new Environment(new ArrayLoader([
            'emails/alert.html.twig' => 'template'
        ])), 'sender@fireping.local');
        $mailer->setParameters([
            'recipient' => 'receiver@fireping.local'
        ]);
        $mailer->trigger($this->createAlert());
    }

    private function createAlert(): Alert
    {
        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        return $alert;
    }

    public function testFailedToSendClearEvent(): void
    {
        $this->expectException(ClearException::class);
        $this->expectExceptionMessage('Failed to mail clear event to receiver@fireping.local');

        $mailer = new Mail($this->createMockSwiftMailer([0]), new TestLogger(), new Environment(new ArrayLoader([
            'emails/alert.html.twig' => 'template'
        ])), 'sender@fireping.local');
        $mailer->setParameters([
            'recipient' => 'receiver@fireping.local'
        ]);
        $mailer->clear($this->createAlert());
    }

    public function testTwigErrorsOnTriggerAreWrappedAsClearException(): void
    {
        $this->expectException(TriggerException::class);

        $mailer = new Mail($this->createMockSwiftMailer([]), new TestLogger(), new Environment(new ArrayLoader([])), 'sender@fireping.local');
        $mailer->setParameters([
            'recipient' => 'receiver@fireping.local'
        ]);
        $mailer->trigger($this->createAlert());
    }

    public function testTwigErrorsOnClearAreWrappedAsClearException(): void
    {
        $this->expectException(ClearException::class);

        $mailer = new Mail($this->createMockSwiftMailer([]), new TestLogger(), new Environment(new ArrayLoader([])), 'sender@fireping.local');
        $mailer->setParameters([
            'recipient' => 'receiver@fireping.local'
        ]);
        $mailer->clear($this->createAlert());
    }
}
