<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Slave\Task\CommandFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CommandPass implements CompilerPassInterface
{
    public const TAG = 'app.slave.command';

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CommandFactory::class)) {
            return;
        }

        $commandFactory = $container->findDefinition(CommandFactory::class);
        $services = $container->findTaggedServiceIds(self::TAG);
        foreach ($services as $id => $tags) {
            $commandFactory->addMethodCall('addCommandType', [new Reference($id)]);
        }
    }
}
