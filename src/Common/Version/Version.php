<?php

declare(strict_types=1);

namespace App\Common\Version;

final class Version implements \Stringable
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

    public function __toString(): string
    {
        return $this->asString();
    }
}

