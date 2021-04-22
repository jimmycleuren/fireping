<?php

declare(strict_types=1);

namespace App\Slave\Process;

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