<?php

declare(strict_types=1);

namespace Marwa\Framework\Providers;

use League\Container\Container;
use Marwa\Framework\Contracts\ServiceProviderInterface;

// Commands live under Marwa\Framework\Console\Commands\*
use Marwa\Framework\Console\Commands\{
    MakeModuleCommand,
    MakeControllerCommand,
    RouteListCommand,
    ServeCommand
};

final class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Bind commands so ConsoleKernel can resolve and register them.
        $container->add(MakeModuleCommand::class)->addArgument($container);
        $container->add(MakeControllerCommand::class)->addArgument($container);
        $container->add(RouteListCommand::class)->addArgument($container);
        $container->add(ServeCommand::class)->addArgument($container);

        // Provide a simple list of command classes for ConsoleKernel to iterate.
        $container->add('console.commands', [
            MakeModuleCommand::class,
            MakeControllerCommand::class,
            RouteListCommand::class,
            ServeCommand::class,
        ]);
    }

    public function boot(Container $container): void
    {
        // No-op. ConsoleKernel will pull 'console.commands' and wire them into Symfony Console.
    }
}
