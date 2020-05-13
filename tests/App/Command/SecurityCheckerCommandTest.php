<?php

namespace Tests\App\Command;

use SensioLabs\Security\Command\SecurityCheckerCommand;
use SensioLabs\Security\SecurityChecker;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SecurityCheckerCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new SecurityCheckerCommand(new SecurityChecker()));

        $command = $application->find('security:check');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName()
        ));
        $this->assertMatchesRegularExpression('/No packages have known vulnerabilities/', $commandTester->getDisplay());
    }
}