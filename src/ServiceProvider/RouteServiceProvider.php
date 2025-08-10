<?php

declare(strict_types=1);

namespace Marwa\App\ServiceProvider;

use Marwa\App\Routes\RouteDirectoryLoader;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Marwa\App\Routes\Router;


final class RouteServiceProvider extends AbstractServiceProvider
{

    public function provides(string $id): bool
    {
        $services = ['router'];

        return in_array($id, $services);
    }
    public function register(): void
    {

        $this->getContainer()->addShared('router', function () {
            return new Router(app(), app('request'), config('app.middlewares'));
        });

        //$loader = new RouteDirectoryLoader(ROUTES_PATH);
        //$loader->load();
    }
}
