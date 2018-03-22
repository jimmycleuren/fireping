<?php

namespace Tests\AppBundle\AlertDestination;

use AppBundle\AlertDestination\Mail;
use AppBundle\Entity\Alert;
use AppBundle\Entity\AlertRule;
use AppBundle\Entity\Device;
use AppBundle\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class MailTest extends TestCase
{
    public function testTrigger()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $mailer->send(Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $templating = $this->prophesize('Symfony\\Bundle\\TwigBundle\\TwigEngine');
        $templating->render(Argument::type('string'), Argument::type('array'))->shouldBeCalledTimes(1);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());
        $mail->setParameters(array('recipient' => 'test@test.com'));

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
        $templating = $this->prophesize('Symfony\\Bundle\\TwigBundle\\TwigEngine');

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());
        $mail->setParameters(array('recipient' => 'invalid'));

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

    public function testClear()
    {
        $mailer = $this->prophesize('Swift_Mailer');
        $mailer->send(Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $templating = $this->prophesize('Symfony\\Bundle\\TwigBundle\\TwigEngine');
        $templating->render(Argument::type('string'), Argument::type('array'))->shouldBeCalledTimes(1);

        $mail = new Mail($mailer->reveal(), $logger->reveal(), $templating->reveal());
        $mail->setParameters(array('recipient' => 'test@test.com'));

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