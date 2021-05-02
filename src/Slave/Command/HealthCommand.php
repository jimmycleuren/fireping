<?php
declare(strict_types=1);

namespace App\Slave\Command;

use App\Slave\Client\FirepingClient;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HealthCommand extends Command
{
    protected static $defaultName = 'app:slave:health';
    /**
     * @var FirepingClient
     */
    private $client;

    public function __construct(FirepingClient $client, string $name = null)
    {
        parent::__construct($name);
        $this->client = $client;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->client->request('GET', '/api/slaves/health');
            $output->writeln('healthy');
            return Command::SUCCESS;
        } catch (GuzzleException $e) {
            $output->writeln('unhealthy');
            return Command::FAILURE;
        }
    }
}
