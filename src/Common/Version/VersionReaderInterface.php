<?php

declare(strict_types=1);

namespace App\Common\Version;

interface VersionReaderInterface
{
    public function version(): Version;
}

