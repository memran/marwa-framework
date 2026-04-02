<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class DatabaseConfig
{
    public const KEY = 'database';

    /**
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     connections: array<string, array<string, mixed>>,
     *     debug: bool,
     *     useDebugPanel: bool,
     *     migrationsPath: string,
     *     seedersPath: string,
     *     seedersNamespace: string
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => false,
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $app->basePath('database/database.sqlite'),
                    'debug' => false,
                ],
            ],
            'debug' => false,
            'useDebugPanel' => false,
            'migrationsPath' => $app->basePath('database/migrations'),
            'seedersPath' => $app->basePath('database/seeders'),
            'seedersNamespace' => 'Database\\Seeders',
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     connections: array<string, array<string, mixed>>,
     *     debug: bool,
     *     useDebugPanel: bool,
     *     migrationsPath: string,
     *     seedersPath: string,
     *     seedersNamespace: string
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        /** @var array<string, array<string, mixed>> $connections */
        $connections = array_values($overrides['connections'] ?? []) === ($overrides['connections'] ?? null)
            ? $defaults['connections']
            : array_replace_recursive($defaults['connections'], is_array($overrides['connections'] ?? null) ? $overrides['connections'] : []);

        foreach ($connections as &$connection) {
            $connection['debug'] = (bool) ($connection['debug'] ?? ($overrides['debug'] ?? $defaults['debug']));
        }
        unset($connection);

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'default' => is_string($overrides['default'] ?? null) && $overrides['default'] !== ''
                ? $overrides['default']
                : $defaults['default'],
            'connections' => $connections,
            'debug' => (bool) ($overrides['debug'] ?? $defaults['debug']),
            'useDebugPanel' => (bool) ($overrides['useDebugPanel'] ?? $defaults['useDebugPanel']),
            'migrationsPath' => is_string($overrides['migrationsPath'] ?? null) && $overrides['migrationsPath'] !== ''
                ? $overrides['migrationsPath']
                : $defaults['migrationsPath'],
            'seedersPath' => is_string($overrides['seedersPath'] ?? null) && $overrides['seedersPath'] !== ''
                ? $overrides['seedersPath']
                : $defaults['seedersPath'],
            'seedersNamespace' => is_string($overrides['seedersNamespace'] ?? null) && $overrides['seedersNamespace'] !== ''
                ? $overrides['seedersNamespace']
                : $defaults['seedersNamespace'],
        ];
    }

    /**
     * @param array{
     *     default: string,
     *     connections: array<string, array<string, mixed>>
     * } $config
     * @return array<string, mixed>
     */
    public static function toPackageConfig(array $config): array
    {
        return [
            'default' => $config['default'],
            'connections' => $config['connections'],
        ];
    }
}
