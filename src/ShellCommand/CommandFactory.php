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
        $this->types[get_class($command)] = $command;
    }

    public function make(string $command, array $args): ?CommandInterface
    {
        $class = $this->types[$command] ?? null;

        if ($class === null) {
            return null;
        }

        $class->setArgs($args);
        return $class;
    }

    public function getTypes()
    {
        return $this->types;
    }
}