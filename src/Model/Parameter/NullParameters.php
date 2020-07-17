<?php

declare(strict_types=1);

namespace App\Model\Parameter;

class NullParameters extends DynamicParameters
{
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