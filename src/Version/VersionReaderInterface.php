<?php

declare(strict_types=1);

namespace App\Version;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

interface VersionReaderInterface
{
    public function version(): Version;
}

