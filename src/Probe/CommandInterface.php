<?php
declare(strict_types=1);

namespace App\Probe;

interface CommandInterface
{
    public function setArgs(array $args): void;
    public function execute(): array;
    public function getType(): string;
}