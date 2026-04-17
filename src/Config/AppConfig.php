<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Middlewares\DebugbarMiddleware;
use Marwa\Framework\Middlewares\MaintenanceMiddleware;
use Marwa\Framework\Middlewares\RequestIdMiddleware;
use Marwa\Framework\Middlewares\RouterMiddleware;
use Marwa\Framework\Middlewares\SecurityMiddleware;
use Marwa\Framework\Middlewares\SessionMiddleware;
use Marwa\Framework\Providers\KernelServiceProvider;

final class AppConfig
{
    public const KEY = 'app';

    /**
     * @return array{
     *     providers: list<class-string>,
     *     middlewares: list<class-string>,
     *     debugbar: bool,
     *     useDebugPanel: bool,
     *     collectors: list<string>,
     *     maintenance: array{template: string|null, message: string},
     *     error404: array{template: string|null}
     * }
     */
    public static function defaults(): array
    {
        return [
            'providers' => [
                KernelServiceProvider::class,
            ],
            'middlewares' => [
                RequestIdMiddleware::class,
                SessionMiddleware::class,
                MaintenanceMiddleware::class,
                SecurityMiddleware::class,
                RouterMiddleware::class,
                DebugbarMiddleware::class,
            ],
            'debugbar' => (bool) env('DEBUGBAR_ENABLED', false),
            'useDebugPanel' => false,
            'collectors' => [],
            'maintenance' => [
                'template' => 'maintenance.twig',
                'message' => 'Service temporarily unavailable for maintenance',
            ],
            'error404' => [
                'template' => 'errors/404.twig',
            ],
        ];
    }
}
