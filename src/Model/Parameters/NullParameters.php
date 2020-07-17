<?php

declare(strict_types=1);

namespace App\Model\Parameters;

class NullParameters extends DynamicParameters
{
    private function __construct()
    {
    }

    public static function fromJsonString(string $json): DynamicParametersInterface
    {
        return new self();
    }

    public function asArray(): array
    {
        return [];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self();
    }
}
