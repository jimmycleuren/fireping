<?php

declare(strict_types=1);

namespace App\Common\Process;

interface ProcessFactoryInterface
{
    public function create(array $command): ProcessInterface;
}