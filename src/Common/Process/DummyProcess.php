<?php

declare(strict_types=1);

namespace App\Common\Process;

class DummyProcess implements ProcessInterface
{
    public function __construct(private readonly string $output, private readonly string $errorOutput, private readonly bool $isSuccessful)
    {
    }

    public static function fromFixture(ProcessFixture $fixture)
    {
        return new self($fixture->getOutput(), $fixture->getErrorOutput(), $fixture->isSuccessful());
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

    public function run(): void
    {
    }
}