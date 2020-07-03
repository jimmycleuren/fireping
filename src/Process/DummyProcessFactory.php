<?php

declare(strict_types=1);

namespace App\Process;

class DummyProcessFactory implements ProcessFactoryInterface
{
    private $fixtures;

    public function addFixture(string $name, ProcessFixture $fixture)
    {
        $this->fixtures[$name] = $fixture;
    }

    public function create(array $command): ProcessInterface
    {
        $name = sha1(serialize($command));
        return DummyProcess::fromFixture($this->fixtures[$name]);
    }
}