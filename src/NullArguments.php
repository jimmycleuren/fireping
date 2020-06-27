<?php

declare(strict_types=1);

namespace App;

class NullArguments extends ProbeArguments
{
    public static function fromJsonString(string $json): ProbeArgumentsInterface
    {
        return new self();
    }

    public function asArray(): array
    {
        return [];
    }
}