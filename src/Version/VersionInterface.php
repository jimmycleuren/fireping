<?php

declare(strict_types=1);

namespace App\Version;

interface VersionInterface
{
    public static function fromString(string $version): VersionInterface;

    public function asString(): string;
}