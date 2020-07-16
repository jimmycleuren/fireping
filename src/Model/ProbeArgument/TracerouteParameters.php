<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

class TracerouteParameters extends JsonParameters
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
}
