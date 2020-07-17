<?php
declare(strict_types=1);

namespace App\Model\Parameter\Probe;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;

class TracerouteParameters extends DynamicParameters
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
