<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Middlewares\DebugbarMiddleware;
use Marwa\Framework\Middlewares\MaintenanceMiddleware;
use Marwa\Framework\Middlewares\RequestIdMiddleware;
use Marwa\Framework\Middlewares\RouterMiddleware;
use Marwa\Framework\Middlewares\SessionMiddleware;
use Marwa\Framework\Providers\KernalServiceProvider;

final class AppConfig
{
    public const KEY = 'app';

    /**
     * @return array{
     *     providers: list<class-string>,
     *     middlewares: list<class-string>,
     *     debugbar: bool,
     *     collectors: list<string>
     * }
     */
    public static function defaults(): array
    {
        return [
            'providers' => [
                KernalServiceProvider::class,
            ],
            'middlewares' => [
                RequestIdMiddleware::class,
                SessionMiddleware::class,
                MaintenanceMiddleware::class,
                RouterMiddleware::class,
                DebugbarMiddleware::class,
            ],
            'debugbar' => false,
            'collectors' => [],
        ];
    }
}
