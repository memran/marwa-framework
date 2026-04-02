<?php

declare(strict_types=1);

namespace Marwa\Framework\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Console\CommandDiscovery;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\ConsoleApplication;
use Marwa\Framework\Console\ConsoleKernel;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class ConsoleServiceProvider extends ServiceProviderAdapter
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            CommandRegistry::class,
            CommandDiscovery::class,
            ConsoleApplication::class,
            ConsoleKernel::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(CommandRegistry::class)
            ->addArgument($container->get(Application::class))
            ->addArgument($container)
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(CommandDiscovery::class)
            ->addArgument($container->get(Application::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(ConsoleApplication::class)
            ->addArgument(ConsoleConfig::defaults($container->get(Application::class))['name'])
            ->addArgument(ConsoleConfig::defaults($container->get(Application::class))['version']);

        $container->addShared(ConsoleKernel::class)
            ->addArgument($container->get(Application::class))
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class))
            ->addArgument($container->get(AppBootstrapper::class))
            ->addArgument($container->get(CommandRegistry::class))
            ->addArgument($container->get(CommandDiscovery::class))
            ->addArgument($container->get(ConsoleApplication::class));
    }
}
