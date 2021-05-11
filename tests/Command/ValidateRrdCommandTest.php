<?php

namespace App\Tests\Command;

use App\Command\ValidateRrdCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateRrdCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $storage = $this->prophesize("App\Storage\RrdStorage");

        $application->add(new ValidateRrdCommand($kernel->getContainer()->get('doctrine')->getManager(), $storage->reveal()));

        $command = $application->find('app:rrd:validate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => 'slave',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
