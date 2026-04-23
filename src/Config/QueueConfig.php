<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class QueueConfig
{
    public const KEY = 'queue';

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'driver' => 'file',
            'default' => 'default',
            'path' => $app->basePath('storage/queue'),
            'database' => [
                'connection' => 'default',
                'table' => 'jobs',
            ],
            'retryAfter' => 90,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $dbConfig = $defaults['database'];
        $overrideDb = $overrides['database'] ?? [];

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'driver' => self::resolveDriver($overrides['driver'] ?? null, $defaults['driver']),
            'default' => is_string($overrides['default'] ?? null) && $overrides['default'] !== ''
                ? $overrides['default']
                : $defaults['default'],
            'path' => is_string($overrides['path'] ?? null) && $overrides['path'] !== ''
                ? $overrides['path']
                : $defaults['path'],
            'database' => [
                'connection' => is_string($overrideDb['connection'] ?? null) && $overrideDb['connection'] !== ''
                    ? $overrideDb['connection']
                    : $dbConfig['connection'],
                'table' => is_string($overrideDb['table'] ?? null) && $overrideDb['table'] !== ''
                    ? $overrideDb['table']
                    : $dbConfig['table'],
            ],
            'retryAfter' => max(1, (int) ($overrides['retryAfter'] ?? $defaults['retryAfter'])),
        ];
    }

    private static function resolveDriver(?string $driver, string $default): string
    {
        if ($driver === null || $driver === '') {
            return $default;
        }

        $driver = strtolower(trim($driver));

        if (!in_array($driver, ['file', 'database'], true)) {
            return $default;
        }

        return $driver;
    }
}
}