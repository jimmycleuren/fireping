<?php
declare(strict_types=1);

namespace App;

interface ProbeArgumentsInterface
{
    public static function fromJsonString(string $json): self;
    public function asArray(): array;
}