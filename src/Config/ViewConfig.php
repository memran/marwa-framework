<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class ViewConfig
{
    public const KEY = 'view';

    /**
     * @return array{
     *     viewsPath: string,
     *     cachePath: string,
     *     debug: bool,
     *     themePath: string,
     *     activeTheme: string,
     *     fallbackTheme: string
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'viewsPath' => $app->basePath('resources/views'),
            'cachePath' => $app->basePath('storage/cache/views'),
            'debug' => (bool) env('APP_DEBUG', false),
            'themePath' => $app->basePath('resources/views/themes'),
            'activeTheme' => 'default',
            'fallbackTheme' => 'default',
        ];
    }
}
