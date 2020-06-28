<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

class HttpArguments extends ProbeArguments
{
    private function __construct()
    {
    }

    public static function fromJsonString(string $json): ProbeArgumentsInterface
    {
        return new self();
    }

    public function asArray(): array
    {
        return [];
    }
}
