<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

interface JsonParametersInterface
{
    public static function fromJsonString(string $json): self;
    public static function fromArray(array $in): self;
    public function asArray(): array;
}
