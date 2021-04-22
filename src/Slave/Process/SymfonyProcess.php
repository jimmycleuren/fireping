<?php

declare(strict_types=1);

namespace App\Slave\Process;

use Symfony\Component\Process\Process;

class SymfonyProcess implements ProcessInterface
{
    /**
     * @var Process
     */
    private $process;

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

    public function run(): void
    {
        $this->process->run();
    }
}