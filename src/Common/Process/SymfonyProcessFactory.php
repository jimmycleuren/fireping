<?php

declare(strict_types=1);

namespace App\Common\Process;

class SymfonyProcessFactory implements ProcessFactoryInterface
{
    public function create(array $command): ProcessInterface
    {
        return new SymfonyProcess($command);
    }
}