<?php
declare(strict_types=1);

namespace App\Factory;

use App\Model\Parameter\DynamicParametersInterface;

interface DynamicParameterFactoryInterface
{
    public function make(string $type, array $args): DynamicParametersInterface;
}