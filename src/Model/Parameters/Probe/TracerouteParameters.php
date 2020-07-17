<?php
declare(strict_types=1);

namespace App\Model\Parameters\Probe;

use App\Model\Parameters\DynamicParameters;
use App\Model\Parameters\DynamicParametersInterface;

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
