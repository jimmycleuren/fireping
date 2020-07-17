<?php

declare(strict_types=1);

namespace App\Model\Parameters;

class NullParameters extends JsonParameters
{
    private function __construct()
    {
    }

    public static function fromJsonString(string $json): JsonParametersInterface
    {
        return new self();
    }

    public function asArray(): array
    {
        return [];
    }

    public static function fromArray(array $in): JsonParametersInterface
    {
        return new self();
    }
}
