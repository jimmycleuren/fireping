<?php

declare(strict_types=1);

namespace App\Version;

use App\Process\ProcessFactoryInterface;
use Psr\Log\LoggerInterface;

class GitVersionReader implements VersionReaderInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ProcessFactoryInterface
     */
    private $factory;

    public function __construct(LoggerInterface $logger, ProcessFactoryInterface $factory)
    {
        $this->logger = $logger;
        $this->factory = $factory;
    }

    public function version(): Version
    {
        $process = $this->factory->create(['git', 'describe', '--always']);
        $process->run();

        $output = $process->getOutput();

        if (!$process->isSuccessful()) {
            $this->logger->info('version_reader: failed to retrieve version via git');
            $this->logger->info(sprintf('version_reader: %s', $output));
            if ($error = trim($process->getErrorOutput())) {
                $this->logger->error(sprintf('version_reader: %s', $error));
            }
        }

        return Version::fromString($output);
    }
}

