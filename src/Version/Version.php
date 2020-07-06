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
        $this->version = trim($version);
    }

    public function asString(): string
    {
        return $this->version;
    }
}

