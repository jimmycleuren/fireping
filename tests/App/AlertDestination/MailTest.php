<?php

namespace App\Tests\App\AlertDestination;

use App\AlertDestination\Mail;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Twig\Environment;

class MailTest extends TestCase
{
    public function testTriggerNoSender()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->error('MAILER_FROM env variable is not set')->shouldBeCalledTimes(1);
        $templating = $this->prophesize(Environment::class);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());

        $original = $_ENV['MAILER_FROM'];
        $_ENV['MAILER_FROM'] = null;
        $mail->trigger(new Alert());
        $_ENV['MAILER_FROM'] = $original;
    }

    public function testTrigger()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $mailer->send(Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $templating = $this->prophesize(Environment::class);
        $templating->render(Argument::type('string'), Argument::type('array'))->shouldBeCalledTimes(1);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());
        $mail->setParameters(['recipient' => 'test@test.com']);

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

        $mail->trigger($alert);
    }

    public function testFailedTrigger()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $mailer->send(Argument::any())->shouldNotBeCalled();
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->error(Argument::type('string'))->shouldBeCalledTimes(1);
        $templating = $this->prophesize(Environment::class);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());
        $mail->setParameters(['recipient' => 'invalid']);

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

        $mail->trigger($alert);
    }

    public function testClearNoSender()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->error('MAILER_FROM env variable is not set')->shouldBeCalledTimes(1);
        $templating = $this->prophesize(Environment::class);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());

        $original = $_ENV['MAILER_FROM'];
        $_ENV['MAILER_FROM'] = null;
        $mail->trigger(new Alert());
        $_ENV['MAILER_FROM'] = $original;
    }

    public function testClear()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $mailer->send(Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $templating = $this->prophesize(Environment::class);
        $templating->render(Argument::type('string'), Argument::type('array'))->shouldBeCalledTimes(1);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());
        $mail->setParameters(['recipient' => 'test@test.com']);

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

        $mail->clear($alert);
    }
}
