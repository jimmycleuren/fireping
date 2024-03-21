<?php
declare(strict_types=1);

namespace App\Slave\Command;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HealthCommand extends Command
{
    protected static $defaultName = 'app:slave:health';

    public function __construct(private readonly ClientInterface $client, string $name = null)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->client->request('GET', '/api/slaves/health');
            $output->writeln('healthy');
            return Command::SUCCESS;
        } catch (GuzzleException) {
            $output->writeln('unhealthy');
            return Command::FAILURE;
        }
    }
}
