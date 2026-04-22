<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class BootstrapConfig
{
    public const KEY = 'bootstrap';

    /**
     * @return array{
     *     configCache:string,
     *     routeCache:string,
     *     moduleCache:string
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'configCache' => (string) env('APP_CONFIG_CACHE', $app->basePath('storage/cache/config.php')),
            'routeCache' => (string) env('APP_ROUTE_CACHE', $app->basePath('storage/cache/routes.php')),
            'moduleCache' => (string) env('APP_MODULE_CACHE', $app->basePath('storage/cache/modules.php')),
        ];
    }
}
