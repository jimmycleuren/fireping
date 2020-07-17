<?php
declare(strict_types=1);

namespace App\Model\Parameters\AlertDestination;

use App\Model\Parameters\JsonParameters;
use App\Model\Parameters\JsonParametersInterface;

class MonologParameters extends JsonParameters
{
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
