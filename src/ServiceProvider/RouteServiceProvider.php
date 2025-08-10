<?php

declare(strict_types=1);

namespace Marwa\App\ServiceProvider;

use Marwa\App\Routes\RouteDirectoryLoader;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Marwa\App\Routes\Router;


final class RouteServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{

    public function provides(string $id): bool
    {
        $services = ['router'];

        return in_array($id, $services);
    }
    public function register(): void
    {
        $this->getContainer()->add('router', function () {
            return new Router(app('request'));
        });
    }

    public function boot(): void
    {
        $loader = new RouteDirectoryLoader(ROUTES_PATH);
        $loader->load();
    }
}
