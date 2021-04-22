<?php

declare(strict_types=1);

namespace App\Slave\Process;

interface ProcessFactoryInterface
{
    public function create(array $command): ProcessInterface;
}