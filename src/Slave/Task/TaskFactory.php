<?php

declare(strict_types=1);

namespace App\Slave\Task;

use Psr\Log\LoggerInterface;

final class TaskFactory
{
    private $logger;

    /**
     * @var TaskInterface[]
     */
    private $types = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addTaskType(TaskInterface $command)
    {
        $this->types[$command->getType()] = $command;
    }

    public function make(string $command, array $args): ?TaskInterface
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
