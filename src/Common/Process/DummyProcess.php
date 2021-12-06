<?php

declare(strict_types=1);

namespace App\Common\Process;

class DummyProcess implements ProcessInterface
{
    private string $output;
    private string $errorOutput;
    private bool $isSuccessful;
    private int $exitCode;
    private int $timeout;

    public function __construct(string $output, string $errorOutput, bool $isSuccessful, int $exitCode = 0)
    {
        $this->output = $output;
        $this->errorOutput = $errorOutput;
        $this->isSuccessful = $isSuccessful;
        $this->exitCode = $exitCode;
    }

    public static function fromFixture(ProcessFixture $fixture): self
    {
        return new self($fixture->getOutput(), $fixture->getErrorOutput(), $fixture->isSuccessful(), $fixture->getExitCode());
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

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function run(): int
    {
        return $this->exitCode;
    }
}
