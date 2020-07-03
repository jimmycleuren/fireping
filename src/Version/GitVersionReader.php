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
    /**
     * @var ProcessFactory
     */
    private $factory;

    public function __construct(LoggerInterface $logger, ProcessFactory $factory)
    {
        $this->logger = $logger;
        $this->factory = $factory;
    }

    public function version(): VersionInterface
    {
        $process = Process::fromShellCommandline('git describe --always');
        $process->run();

        $output = $process->getOutput();

        $this->logger->info('test');

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

class ProcessFactory
{
    public function create(): ProcessInterface
    {
        return new SymfonyProcess($commandLine);
    }
}

interface ProcessInterface
{
    public function getOutput(): string;
    public function getErrorOutput(): string;
    public function isSuccessful(): bool;
    public function execute(): void;
}

class DummyProcess implements ProcessInterface
{
    /**
     * @var string
     */
    private $output;
    /**
     * @var string
     */
    private $errorOutput;
    /**
     * @var bool
     */
    private $isSuccessful;

    public function __construct(string $output, string $errorOutput, bool $isSuccessful)
    {
        $this->output = $output;
        $this->errorOutput = $errorOutput;
        $this->isSuccessful = $isSuccessful;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function execute(): void
    {
    }
}

class SymfonyProcess implements ProcessInterface
{
    /**
     * @var Process
     */
    private $process;

    public function __construct(string $commandLine)
    {
        $this->process = Process::fromShellCommandline($commandLine);
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function execute(): void
    {
        $this->process->run();
    }
}