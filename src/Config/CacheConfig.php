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
     *     file: array{path: string},
     *     sqlite: array{path: string, table: string},
     *     memory: array{limit: int|string|null},
     *     nats: array{
     *         bucket: string,
     *         host: string,
     *         port: int,
     *         servers: list<string>,
     *         user: string|null,
     *         pass: string|null,
     *         token: string|null,
     *         jwt: string|null,
     *         nkey: string|null,
     *         credentials: string|null,
     *         tlsCaFile: string|null,
     *         tlsCertFile: string|null,
     *         tlsKeyFile: string|null,
     *         timeout: int
     *     }
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'driver' => 'file',
            'namespace' => 'default',
            'buffered' => true,
            'transactional' => false,
            'stampede' => [
                'enabled' => false,
                'sla' => 1000,
            ],
            'file' => [
                'path' => $app->basePath('storage/cache/framework'),
            ],
            'sqlite' => [
                'path' => $app->basePath('storage/cache/framework.sqlite'),
                'table' => 'framework_cache',
            ],
            'memory' => [
                'limit' => null,
            ],
            'nats' => [
                'bucket' => 'marwa_cache',
                'host' => '127.0.0.1',
                'port' => 4222,
                'servers' => [],
                'user' => null,
                'pass' => null,
                'token' => null,
                'jwt' => null,
                'nkey' => null,
                'credentials' => null,
                'tlsCaFile' => null,
                'tlsCertFile' => null,
                'tlsKeyFile' => null,
                'timeout' => 1,
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
     *     file: array{path: string},
     *     sqlite: array{path: string, table: string},
     *     memory: array{limit: int|string|null},
     *     nats: array{
     *         bucket: string,
     *         host: string,
     *         port: int,
     *         servers: list<string>,
     *         user: string|null,
     *         pass: string|null,
     *         token: string|null,
     *         jwt: string|null,
     *         nkey: string|null,
     *         credentials: string|null,
     *         tlsCaFile: string|null,
     *         tlsCertFile: string|null,
     *         tlsKeyFile: string|null,
     *         timeout: int
     *     }
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $stampede = is_array($overrides['stampede'] ?? null)
            ? array_replace($defaults['stampede'], $overrides['stampede'])
            : $defaults['stampede'];
        $file = is_array($overrides['file'] ?? null)
            ? array_replace($defaults['file'], $overrides['file'])
            : $defaults['file'];
        $sqlite = is_array($overrides['sqlite'] ?? null)
            ? array_replace($defaults['sqlite'], $overrides['sqlite'])
            : $defaults['sqlite'];
        $memory = is_array($overrides['memory'] ?? null)
            ? array_replace($defaults['memory'], $overrides['memory'])
            : $defaults['memory'];
        $nats = is_array($overrides['nats'] ?? null)
            ? array_replace($defaults['nats'], $overrides['nats'])
            : $defaults['nats'];

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
            'file' => [
                'path' => is_string($file['path'] ?? null) && $file['path'] !== ''
                    ? $file['path']
                    : $defaults['file']['path'],
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
            'nats' => self::resolveNatsConfig($nats, $defaults['nats']),
        ];
    }

    /**
     * @param array<string, mixed> $nats
     * @param array{
     *     bucket: string,
     *     host: string,
     *     port: int,
     *     servers: list<string>,
     *     user: string|null,
     *     pass: string|null,
     *     token: string|null,
     *     jwt: string|null,
     *     nkey: string|null,
     *     credentials: string|null,
     *     tlsCaFile: string|null,
     *     tlsCertFile: string|null,
     *     tlsKeyFile: string|null,
     *     timeout: int
     * } $defaults
     * @return array{
     *     bucket: string,
     *     host: string,
     *     port: int,
     *     servers: list<string>,
     *     user: string|null,
     *     pass: string|null,
     *     token: string|null,
     *     jwt: string|null,
     *     nkey: string|null,
     *     credentials: string|null,
     *     tlsCaFile: string|null,
     *     tlsCertFile: string|null,
     *     tlsKeyFile: string|null,
     *     timeout: int
     * }
     */
    private static function resolveNatsConfig(array $nats, array $defaults): array
    {
        return [
            'bucket' => self::nonEmptyString($nats['bucket'] ?? null, $defaults['bucket']),
            'host' => self::nonEmptyString($nats['host'] ?? null, $defaults['host']),
            'port' => max(1, (int) ($nats['port'] ?? $defaults['port'])),
            'servers' => self::stringList($nats['servers'] ?? $defaults['servers']),
            'user' => self::nullableString($nats['user'] ?? null),
            'pass' => self::nullableString($nats['pass'] ?? null),
            'token' => self::nullableString($nats['token'] ?? null),
            'jwt' => self::nullableString($nats['jwt'] ?? null),
            'nkey' => self::nullableString($nats['nkey'] ?? null),
            'credentials' => self::nullableString($nats['credentials'] ?? null),
            'tlsCaFile' => self::nullableString($nats['tlsCaFile'] ?? null),
            'tlsCertFile' => self::nullableString($nats['tlsCertFile'] ?? null),
            'tlsKeyFile' => self::nullableString($nats['tlsKeyFile'] ?? null),
            'timeout' => max(1, (int) ($nats['timeout'] ?? $defaults['timeout'])),
        ];
    }

    private static function nonEmptyString(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
