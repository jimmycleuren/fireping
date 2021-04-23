<?php

declare(strict_types=1);

namespace App\Common\Version;

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

    public function __toString()
    {
        return $this->asString();
    }
}

