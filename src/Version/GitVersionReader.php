<?php

declare(strict_types=1);

namespace App\Version;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class GitVersionReader implements VersionReaderInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function version(): VersionInterface
    {
        $process = Process::fromShellCommandline('git describe --always');
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