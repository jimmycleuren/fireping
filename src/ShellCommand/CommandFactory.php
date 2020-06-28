<?php

declare(strict_types=1);

namespace App\ShellCommand;

use Psr\Log\LoggerInterface;

final class CommandFactory
{
    private $logger;

    /**
     * @var CommandInterface[]
     */
    private $types = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addCommandType(CommandInterface $command)
    {
        $this->types[$command->getType()] = $command;
    }

    public function make(string $command, array $args): ?CommandInterface
    {
        $class = $this->types[$command] ?? null;

        if (null === $class) {
            throw new \RuntimeException("Cannot create a command of type $command");
        }

        $class->setArgs($args);

        return $class;
    }

    public function getTypes()
    {
        return $this->types;
    }
}
