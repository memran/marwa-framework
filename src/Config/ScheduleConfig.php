<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class ScheduleConfig
{
    public const KEY = 'schedule';

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     lockPath: string,
     *     file: array{path:string},
     *     cache: array{namespace:string},
     *     database: array{connection:string,table:string},
     *     defaultLoopSeconds: int,
     *     defaultSleepSeconds: int
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'lockPath' => $app->basePath('storage/framework/schedule'),
            'driver' => 'file',
            'file' => [
                'path' => $app->basePath('storage/framework/schedule'),
            ],
            'cache' => [
                'namespace' => 'schedule',
            ],
            'database' => [
                'connection' => 'sqlite',
                'table' => 'schedule_jobs',
            ],
            'defaultLoopSeconds' => 1,
            'defaultSleepSeconds' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     lockPath: string,
     *     file: array{path:string},
     *     cache: array{namespace:string},
     *     database: array{connection:string,table:string},
     *     defaultLoopSeconds: int,
     *     defaultSleepSeconds: int
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);

        $filePath = is_string($overrides['file']['path'] ?? null) && $overrides['file']['path'] !== ''
            ? $overrides['file']['path']
            : (
                is_string($overrides['lockPath'] ?? null) && $overrides['lockPath'] !== ''
                    ? $overrides['lockPath']
                    : $defaults['file']['path']
            );

        $driver = is_string($overrides['driver'] ?? null) && in_array($overrides['driver'], ['file', 'database', 'cache'], true)
            ? $overrides['driver']
            : $defaults['driver'];

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'driver' => $driver,
            'lockPath' => $filePath,
            'file' => [
                'path' => $filePath,
            ],
            'cache' => [
                'namespace' => is_string($overrides['cache']['namespace'] ?? null) && $overrides['cache']['namespace'] !== ''
                    ? $overrides['cache']['namespace']
                    : $defaults['cache']['namespace'],
            ],
            'database' => [
                'connection' => is_string($overrides['database']['connection'] ?? null) && $overrides['database']['connection'] !== ''
                    ? $overrides['database']['connection']
                    : $defaults['database']['connection'],
                'table' => is_string($overrides['database']['table'] ?? null) && $overrides['database']['table'] !== ''
                    ? $overrides['database']['table']
                    : $defaults['database']['table'],
            ],
            'defaultLoopSeconds' => max(1, (int) ($overrides['defaultLoopSeconds'] ?? $defaults['defaultLoopSeconds'])),
            'defaultSleepSeconds' => max(1, (int) ($overrides['defaultSleepSeconds'] ?? $defaults['defaultSleepSeconds'])),
        ];
    }
}
