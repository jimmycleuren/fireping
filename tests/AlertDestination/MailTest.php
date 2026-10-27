<?php

declare(strict_types=1);

namespace App\Tests\AlertDestination;

use App\AlertDestination\Mail;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use App\Tests\Doubles\CollectingTransport;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Mailer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use UnexpectedValueException;
use function file_get_contents;

class MailTest extends TestCase
{
    private CollectingTransport $transport;
    private Mailer $mailer;
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
        self::assertCount(1, $this->transport->getSentMessages());
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
        self::assertCount(1, $this->transport->getSentMessages());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = new CollectingTransport();
        $this->mailer = new Mailer($this->transport);
        $this->templating = new Environment(new ArrayLoader([
            'emails/alert.html.twig' => file_get_contents(__DIR__ . '/../../templates/emails/alert.html.twig'),
            'emails/alert.txt.twig' => file_get_contents(__DIR__ . '/../../templates/emails/alert.txt.twig')
        ]));
        $this->mail = new Mail($this->mailer, new NullLogger(), $this->templating, "fireping@example.com");
    }
}
