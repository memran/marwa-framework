<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'route:list', description: 'List registered application routes.')]
final class RouteListCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->container()->get(AppBootstrapper::class)->bootstrap();
        $container = $this->container();

        if (!$container->has(RouterAdapter::class)) {
            $container->addShared(RouterAdapter::class, function () use ($container): RouterAdapter {
                return new RouterAdapter($container);
            });
        }

        /** @var RouterAdapter $router */
        $router = $container->get(RouterAdapter::class);
        $routes = $router->routes();

        if ($routes === []) {
            $routeCache = BootstrapConfig::defaults($this->app())['routeCache'];

            if (is_file($routeCache)) {
                $router->loadCompiledRoutesFrom($routeCache);
            } else {
                $this->loadRoutesFromSource();
            }

            $routes = $router->routes();
        }

        $output->writeln('<comment>Registered routes:</comment>');

        if ($routes === []) {
            $output->writeln('  (none)');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('%-12s %-40s %-28s %-24s %s', 'METHODS', 'PATH', 'NAME', 'DOMAIN', 'HANDLER'));
        $output->writeln(str_repeat('-', 120));

        foreach ($routes as $route) {
            $output->writeln(sprintf(
                '%-12s %-40s %-28s %-24s %s',
                implode('|', $route['methods']),
                $route['path'],
                (string) ($route['name'] ?? ''),
                (string) ($route['domain'] ?? '*'),
                $this->formatHandler($route['controller'], $route['action'])
            ));
        }

        return Command::SUCCESS;
    }

    private function loadRoutesFromSource(): void
    {
        $web = $this->app()->basePath('routes/web.php');
        $api = $this->app()->basePath('routes/api.php');

        if (is_file($web)) {
            require $web;
        }

        if (is_file($api)) {
            require $api;
        }

        $moduleBootstrapper = $this->app()->make(ModuleBootstrapper::class);
        $registry = $moduleBootstrapper->registry();

        if ($registry === null) {
            return;
        }

        foreach ($registry->all() as $module) {
            foreach (['http', 'api'] as $channel) {
                $routeFile = $module->routeFile($channel);

                if (is_string($routeFile) && is_file($routeFile)) {
                    require $routeFile;
                }
            }
        }
    }

    private function formatHandler(?string $controller, ?string $action): string
    {
        if ($controller !== null && $controller !== '' && $action !== null && $action !== '') {
            return $controller . '::' . $action;
        }

        return '(closure/invokable)';
    }
}
