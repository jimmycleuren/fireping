<?php

declare(strict_types=1);

namespace App\Common\Version;

use App\Common\Process\ProcessFactoryInterface;
use Psr\Log\LoggerInterface;

class GitVersionReader implements VersionReaderInterface
{
    public function __construct(private readonly LoggerInterface $logger, private readonly ProcessFactoryInterface $factory)
    {
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

        return new Version($output);
    }
}

