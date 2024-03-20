<?php

declare(strict_types=1);

namespace App\Slave\Task;

final class TaskFactory
{

    /**
     * @var TaskInterface[]
     */
    private $types = [];

    public function addTaskType(TaskInterface $task)
    {
        $this->types[$task->getType()] = $task;
    }

    public function make(string $task, array $args): TaskInterface
    {
        $class = $this->types[$task] ?? null;

        if (null === $class) {
            throw new \RuntimeException("Cannot create a task of type $task");
        }

        $class->setArgs($args);

        return $class;
    }

    public function getTypes()
    {
        return $this->types;
    }
}
