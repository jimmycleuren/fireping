<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Slave\Task\TaskFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddTasksPass implements CompilerPassInterface
{
    public const TAG = 'app.slave.command';

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(TaskFactory::class)) {
            return;
        }

        $commandFactory = $container->findDefinition(TaskFactory::class);
        $services = $container->findTaggedServiceIds(self::TAG);
        foreach ($services as $id => $tags) {
            $commandFactory->addMethodCall('addTaskType', [new Reference($id)]);
        }
    }
}
