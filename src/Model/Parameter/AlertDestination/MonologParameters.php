<?php
declare(strict_types=1);

namespace App\Model\Parameter\AlertDestination;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;

class MonologParameters extends DynamicParameters
{
    public function asArray(): array
    {
        return [];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self();
    }
}
