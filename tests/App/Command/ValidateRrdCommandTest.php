<?php

namespace App\Tests\App\Command;

use App\Command\ValidateRrdCommand;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateRrdCommandTest extends KernelTestCase
{
    use ProphecyTrait;

    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $storage = $this->prophesize("App\Storage\RrdStorage");

        $application->add(new ValidateRrdCommand($kernel->getContainer()->get('doctrine')->getManager(), $storage->reveal()));

        $command = $application->find('app:rrd:validate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--env' => 'slave'
        ));

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}