<?php

declare(strict_types=1);

namespace App\Common\Process;

use Symfony\Component\Process\Process;

class SymfonyProcess implements ProcessInterface
{
    private Process $process;

    public function __construct(array $command)
    {
        $this->process = new Process($command);
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

    public function setTimeout(int $timeout): void
    {
        $this->process->setTimeout($timeout);
    }

    public function run(): int
    {
        return $this->process->run();
    }
}
