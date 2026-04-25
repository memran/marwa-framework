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
        $driver = env('SCHEDULE_DRIVER', 'file');

        return [
            'enabled' => true,
            'lockPath' => $app->basePath('storage/framework/schedule'),
            'driver' => self::resolveDriver(is_string($driver) ? $driver : null, 'file'),
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
            'defaultLoopSeconds' => 0,
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

        $driver = self::resolveDriver($overrides['driver'] ?? null, $defaults['driver']);
        $databaseTable = is_string($overrides['database']['table'] ?? null) && $overrides['database']['table'] !== ''
            ? $overrides['database']['table']
            : $defaults['database']['table'];

        if (!self::isIdentifier($databaseTable)) {
            $databaseTable = $defaults['database']['table'];
        }

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
                'table' => $databaseTable,
            ],
            'defaultLoopSeconds' => max(0, (int) ($overrides['defaultLoopSeconds'] ?? $defaults['defaultLoopSeconds'])),
            'defaultSleepSeconds' => max(1, (int) ($overrides['defaultSleepSeconds'] ?? $defaults['defaultSleepSeconds'])),
        ];
    }

    private static function resolveDriver(mixed $driver, string $default): string
    {
        if (!is_string($driver) || $driver === '') {
            return $default;
        }

        $driver = strtolower(trim($driver));

        if (!in_array($driver, ['file', 'database', 'cache', 'redis'], true)) {
            return $default;
        }

        return $driver;
    }

    private static function isIdentifier(string $value): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
    }
}
