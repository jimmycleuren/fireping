<?php

declare(strict_types=1);

namespace App\Common\Process;

class ProcessFixture
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