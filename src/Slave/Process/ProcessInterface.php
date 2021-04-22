<?php

declare(strict_types=1);

namespace App\Slave\Process;

interface ProcessInterface
{
    public function getOutput(): string;

    public function getErrorOutput(): string;

    public function isSuccessful(): bool;

    public function run(): void;
}