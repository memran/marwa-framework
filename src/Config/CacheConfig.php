<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class CacheConfig
{
    public const KEY = 'cache';

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     namespace: string,
     *     buffered: bool,
     *     transactional: bool,
     *     stampede: array{enabled: bool, sla: int},
     *     sqlite: array{path: string, table: string},
     *     memory: array{limit: int|string|null}
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'driver' => extension_loaded('pdo_sqlite') ? 'sqlite' : 'memory',
            'namespace' => 'default',
            'buffered' => true,
            'transactional' => false,
            'stampede' => [
                'enabled' => false,
                'sla' => 1000,
            ],
            'sqlite' => [
                'path' => $app->basePath('storage/cache/framework.sqlite'),
                'table' => 'framework_cache',
            ],
            'memory' => [
                'limit' => null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     namespace: string,
     *     buffered: bool,
     *     transactional: bool,
     *     stampede: array{enabled: bool, sla: int},
     *     sqlite: array{path: string, table: string},
     *     memory: array{limit: int|string|null}
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $stampede = is_array($overrides['stampede'] ?? null)
            ? array_replace($defaults['stampede'], $overrides['stampede'])
            : $defaults['stampede'];
        $sqlite = is_array($overrides['sqlite'] ?? null)
            ? array_replace($defaults['sqlite'], $overrides['sqlite'])
            : $defaults['sqlite'];
        $memory = is_array($overrides['memory'] ?? null)
            ? array_replace($defaults['memory'], $overrides['memory'])
            : $defaults['memory'];

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'driver' => is_string($overrides['driver'] ?? null) && $overrides['driver'] !== ''
                ? strtolower($overrides['driver'])
                : $defaults['driver'],
            'namespace' => is_string($overrides['namespace'] ?? null) && $overrides['namespace'] !== ''
                ? $overrides['namespace']
                : $defaults['namespace'],
            'buffered' => (bool) ($overrides['buffered'] ?? $defaults['buffered']),
            'transactional' => (bool) ($overrides['transactional'] ?? $defaults['transactional']),
            'stampede' => [
                'enabled' => (bool) ($stampede['enabled'] ?? $defaults['stampede']['enabled']),
                'sla' => max(1, (int) ($stampede['sla'] ?? $defaults['stampede']['sla'])),
            ],
            'sqlite' => [
                'path' => is_string($sqlite['path'] ?? null) && $sqlite['path'] !== ''
                    ? $sqlite['path']
                    : $defaults['sqlite']['path'],
                'table' => is_string($sqlite['table'] ?? null) && $sqlite['table'] !== ''
                    ? $sqlite['table']
                    : $defaults['sqlite']['table'],
            ],
            'memory' => [
                'limit' => $memory['limit'] ?? $defaults['memory']['limit'],
            ],
        ];
    }
}
