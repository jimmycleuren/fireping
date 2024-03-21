<?php

declare(strict_types=1);

namespace App\Common\Process;

class ProcessFixture
{
    public function __construct(private readonly string $output, private readonly string $errorOutput, private readonly bool $isSuccessful)
    {
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }
}