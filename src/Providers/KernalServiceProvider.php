<?php

declare(strict_types=1);

namespace Marwa\Framework\Providers;

use Marwa\Framework\Adapters\DebugbarAdapter;
use Marwa\Framework\Adapters\{RouterAdapter, ViewAdapter};
use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Contracts\BootServiceProviderInterface;
use Marwa\Framework\Supports\Config;

final class KernalServiceProvider extends ServiceProviderAdapter implements BootServiceProviderInterface
{
    public function provides(string $id): bool
    {
        $services = [
            'debugbar',
            ViewAdapter::class,
            RouterAdapter::class,
        ];

        return in_array($id, $services, true);
    }
    public function register(): void
    {
        /** @var Config $config */
        $config = $this->getContainer()->get(Config::class);
        $defaults = AppConfig::defaults();

        if ($config->getBool(AppConfig::KEY . '.debugbar', $defaults['debugbar'])) {
            $this->getContainer()->addShared('debugbar', function () {
                /** @var Config $config */
                $config = $this->getContainer()->get(Config::class);
                $collectors = $config->getArray(AppConfig::KEY . '.collectors', AppConfig::defaults()['collectors']);
                $bar = new DebugbarAdapter($collectors);
                $bar->registerCollectors();
                return $bar->getDebugger();
            });
        }

        $this->getContainer()->addShared(ViewAdapter::class)
            ->addArgument($this->getContainer()->get(Application::class))
            ->addArgument($config);
    }

    public function boot(): void
    {
        $this->getContainer()->addShared(RouterAdapter::class)
            ->addArgument($this->getContainer());

        /** @var Application $app */
        $app = $this->getContainer()->get(Application::class);
        /** @var RouterAdapter $router */
        $router = $this->getContainer()->get(RouterAdapter::class);
        $routeCache = BootstrapConfig::defaults($app)['routeCache'];

        if (\is_file($routeCache)) {
            $router->loadCompiledRoutesFrom($routeCache);
            return;
        }

        $web = $app->basePath('routes/web.php');
        $api = $app->basePath('routes/api.php');

        if (\is_file($web)) {
            require $web;
        }
        if (\is_file($api)) {
            require $api;
        }
    }
}
