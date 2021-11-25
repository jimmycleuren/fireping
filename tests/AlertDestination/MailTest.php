<?php

declare(strict_types=1);

namespace App\Tests\AlertDestination;

use App\AlertDestination\Mail;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use App\Tests\Doubles\MockTransport;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Swift_Mailer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use UnexpectedValueException;
use function file_get_contents;

class MailTest extends TestCase
{
    private MockTransport $transport;
    private Swift_Mailer $mailer;
    private Environment $templating;
    private Mail $mail;

    public function testMailExpectsValidEmailAddress(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('invalid e-mail address');
        new Mail($this->mailer, new NullLogger(), $this->templating, '');
    }

    public function testSetParametersExpectsRecipientKey(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('mail requires recipient to be set');
        $this->mail->setParameters([]);
    }

    public function testSetParametersExpectsRecipientToBeString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('recipient must be a string');
        $this->mail->setParameters(['recipient' => null]);
    }

    public function testSetParametersExpectsRecipientToBeEmail(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('invalid recipient e-mail address');
        $this->mail->setParameters(['recipient' => '']);
    }

    public function testTrigger(): void
    {
        $this->mail->setParameters(['recipient' => 'test@example.com']);
        $this->mail->trigger($this->createDefaultAlert());
        self::assertEquals(1, $this->transport->getSent());
    }

    private function createDefaultAlert(): Alert
    {
        $rule = new AlertRule();
        $rule->setMessageUp('up');
        $rule->setMessageDown('down');
        $rule->setName('rule');

        $device = new Device();
        $device->setName('device');

        $group = new SlaveGroup();
        $group->setName('group');

        $alert = new Alert();
        $alert->setAlertRule($rule);
        $alert->setDevice($device);
        $alert->setSlaveGroup($group);

        return $alert;
    }

    public function testClear(): void
    {
        $this->mail->setParameters(['recipient' => 'test@example.com']);
        $this->mail->clear($this->createDefaultAlert());
        self::assertEquals(1, $this->transport->getSent());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = new MockTransport();
        $this->mailer = new Swift_Mailer($this->transport);
        $this->templating = new Environment(new ArrayLoader([
            'emails/alert.html.twig' => file_get_contents(__DIR__ . '/../../templates/emails/alert.html.twig')
        ]));
        $this->mail = new Mail($this->mailer, new NullLogger(), $this->templating, "fireping@example.com");
    }
}
