<?php

declare(strict_types=1);

namespace App\Version;

final class Version
{
    /**
     * @var string
     */
    private $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public static function fromString(string $version): self
    {
        return new self(trim($version));
    }

    public function asString(): string
    {
        return $this->version;
    }

    public function __toString()
    {
        return $this->version;
    }
}

