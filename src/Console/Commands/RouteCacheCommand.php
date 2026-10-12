<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\Bootstrappers\RouteFileLoader;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Console\AbstractCommand;
use Marwa\Router\Exceptions\UncacheableRouteException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'route:cache', description: 'Compile routes into the bootstrap cache directory.')]
final class RouteCacheCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!BootstrapConfig::shouldCache()) {
            $output->writeln('<comment>Route cache is production-only. Skipping cache build.</comment>');

            return Command::SUCCESS;
        }

        $this->container()->get(AppBootstrapper::class)->bootstrap();

        // Compile from the route files, never from a previously compiled cache,
        // on a router of its own so routes already registered are not doubled.
        $router = new RouterAdapter($this->container());
        $this->container()->addShared(RouterAdapter::class, $router, true);

        $loader = new RouteFileLoader($this->app());
        $loader->loadAppRoutes();
        $loader->loadModuleRoutes($this->app()->make(ModuleBootstrapper::class)->registry());

        $file = BootstrapConfig::defaults($this->app())['routeCache'];
        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        try {
            $router->compileRoutesTo($file);
        } catch (UncacheableRouteException $exception) {
            $output->writeln(sprintf('<error>Unable to cache routes: %s</error>', $exception->getMessage()));
            $output->writeln('Use [Controller::class, \'method\'] handlers instead of closures, and middleware class names instead of instances.');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Route cache created:</info> %s (%d routes)', $file, count($router->routes())));

        return Command::SUCCESS;
    }
}
