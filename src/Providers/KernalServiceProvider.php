<?php

declare(strict_types=1);

namespace Marwa\Framework\Providers;

use Marwa\Framework\Contracts\BootServiceProviderInterface;
use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Adapters\DebugbarAdapter;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Facades\Config;
use Marwa\Framework\Adapters\{RouterAdapter, ViewAdapter};

final class KernalServiceProvider extends ServiceProviderAdapter implements BootServiceProviderInterface
{
    public function provides(string $id): bool
    {
        $services = [
            'debugbar',
            ViewAdapter::class,
            RouterAdapter::class
        ];

        return in_array($id, $services);
    }
    public function register(): void
    {
        /**
         * Register Debug Collector
         */
        if (Config::getBool('app.debugbar')) {
            $this->getContainer()->addShared('debugbar', function () {
                $bar = new DebugbarAdapter(Config::getArray('app.collectors'));
                $bar->registerCollectors();
                return $bar->getDebugger();
            });
        }


        /**
         * Add View Engine
         */
        $this->getContainer()->addShared(ViewAdapter::class);
    }

    public function boot(): void
    {
        /**
         * Boot Router
         */
        $this->getContainer()->addShared(RouterAdapter::class)
            ->addArgument($this->getContainer());

        $web = routes_path() . DIRECTORY_SEPARATOR . 'web.php';
        $api = routes_path() . DIRECTORY_SEPARATOR . 'api.php';

        if (\is_file($web)) {
            require $web;
        }
        if (\is_file($api)) {
            require $api;
        }
    }
}
