<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class LoggerConfig
{
    public const KEY = 'logger';

    /**
     * @return array{
     *     enable: bool,
     *     filter: list<string>,
     *     storage: array{
     *         driver: string,
     *         path: string,
     *         prefix: string
     *     }
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enable' => (bool) env('LOG_ENABLE', true),
            'filter' => [],
            'storage' => [
                'driver' => (string) env('LOG_CHANNEL', 'file'),
                'path' => $app->basePath('storage/logs'),
                'prefix' => 'marwa',
            ],
        ];
    }
}
