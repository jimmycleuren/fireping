<?php

namespace App\Tests;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrationTest extends WebTestCase
{
    protected static $application;

    protected function setUp() : void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $connection = $em->getConnection();
        $driver = $connection->getDriver();

        if (!$driver instanceof AbstractMySQLDriver) {
            $this->markTestSkipped('This test requires MySQL.');
        }

        try {
            if (in_array('fireping_test', $connection->getSchemaManager()->listDatabases())) {
                $schemaTool = new SchemaTool($em);
                $schemaTool->dropDatabase();
            }
        } catch (\Exception $e) {
            $this->fail('Could not cleanup test database for migration test: ' . $e->getMessage());
        }
    }

    protected static function runCommand($command, $parameters)
    {
        //$command = sprintf('%s --quiet', $command);

        $command .= " $parameters";

        $output = new BufferedOutput();
        $code = self::getApplication()->run(new StringInput($command), $output);
        //var_dump($code, $output->fetch());
        return $output->fetch();
    }

    protected static function getApplication()
    {
        if (null === self::$application) {
            self::ensureKernelShutdown();
            $client = static::createClient();

            self::$application = new Application($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }

    public function testMigrations()
    {
        // Test if all migrations run through
        $output = $this->runCommand('doctrine:migrations:migrate', '--no-interaction --env=test');
        $this->assertMatchesRegularExpression('/\d+ sql queries\n$/', $output);

        // Validate that the mapping files are correct and in sync with the database.
        $output = $this->runCommand('doctrine:schema:validate', '--env=test');
        $this->assertStringContainsString('[OK] The mapping files are correct.', $output);

        $output = $this->runCommand('doctrine:schema:update', '--env=test --dump-sql');
        $this->assertStringContainsString('[OK] Nothing to update', $output);
    }

    public function testRollback()
    {
        self::$application = null;

        // Test if all migrations run through
        $output = $this->runCommand('doctrine:migrations:migrate', '--no-interaction --env=test');
        $this->assertMatchesRegularExpression('/\d+ sql queries\n$/', $output);

        self::$application = null;

        // Test if all migrations run back
        $output = $this->runCommand('doctrine:migrations:migrate', 'first --no-interaction --env=test');
        $this->assertMatchesRegularExpression('/\d+ sql queries\n$/', $output);
    }

    protected function tearDown() : void
    {
        $output = $this->runCommand('doctrine:schema:create --force', '--no-interaction --env=test');
    }
}
