<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Console\CommandDiscovery;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\ConsoleApplication;
use Marwa\Framework\Console\ConsoleKernel;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class CoreBindingsBootstrapper
{
    public function bootstrap(Application $app, Container $container): void
    {
        date_default_timezone_set((string) env('TIMEZONE', 'Asia/Dhaka'));

        $container->addShared(Application::class, $app);
        $container->addShared(Container::class, $container);

        $container->addShared(Config::class)
            ->addArgument($app->basePath('config'));

        $container->addShared(LoggerAdapter::class, function () use ($app, $container) {
            return (new LoggerAdapter($app, $container->get(Config::class)))->getLogger();
        });

        $container->addShared(LoggerInterface::class, function () use ($container) {
            return $container->get(LoggerAdapter::class);
        });

        $container->addShared(EventDispatcherAdapter::class)
            ->addArgument($container)
            ->addArgument($container->get(Config::class));

        $container->addShared(EventDispatcherInterface::class, function () use ($container) {
            return $container->get(EventDispatcherAdapter::class);
        });

        $container->addShared(ErrorHandlerAdapter::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(ErrorHandlerBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container->get(ErrorHandlerAdapter::class));

        $container->addShared(DatabaseBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(AppBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(ProviderBootstrapper::class))
            ->addArgument($container->get(ErrorHandlerBootstrapper::class))
            ->addArgument($container->get(DatabaseBootstrapper::class))
            ->addArgument($container->get(ModuleBootstrapper::class));

        $container->addShared(ModuleBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container)
            ->addArgument($container->get(Config::class));

        $container->addShared(CommandRegistry::class)
            ->addArgument($app)
            ->addArgument($container)
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(CommandDiscovery::class)
            ->addArgument($app)
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(ConsoleApplication::class)
            ->addArgument(ConsoleConfig::defaults($app)['name'])
            ->addArgument(ConsoleConfig::defaults($app)['version']);

        $container->addShared(ConsoleKernel::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class))
            ->addArgument($container->get(AppBootstrapper::class))
            ->addArgument($container->get(CommandRegistry::class))
            ->addArgument($container->get(CommandDiscovery::class))
            ->addArgument($container->get(ConsoleApplication::class));
    }
}
