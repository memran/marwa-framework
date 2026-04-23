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
     *     default: string,
     *     path: string,
     *     retryAfter: int
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'default' => 'default',
            'path' => $app->basePath('storage/framework/queue'),
            'retryAfter' => 90,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     path: string,
     *     retryAfter: int
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'default' => is_string($overrides['default'] ?? null) && $overrides['default'] !== ''
                ? $overrides['default']
                : $defaults['default'],
            'path' => is_string($overrides['path'] ?? null) && $overrides['path'] !== ''
                ? $overrides['path']
                : $defaults['path'],
            'retryAfter' => max(1, (int) ($overrides['retryAfter'] ?? $defaults['retryAfter'])),
        ];
    }
}
