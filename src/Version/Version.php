<?php

declare(strict_types=1);

namespace App\Version;

class Version implements VersionInterface
{
    /**
     * @var string
     */
    private $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public static function fromString(string $version): VersionInterface
    {
        return new self(trim($version));
    }

    public function asString(): string
    {
        return $this->version;
    }
}

