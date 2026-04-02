<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class StorageConfig
{
    public const KEY = 'storage';

    /**
     * @return array{
     *     default: string,
     *     disks: array<string, array<string, mixed>>
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => $app->basePath('storage/app'),
                    'visibility' => 'private',
                ],
                'public' => [
                    'driver' => 'local',
                    'root' => $app->basePath('storage/app/public'),
                    'visibility' => 'public',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     default: string,
     *     disks: array<string, array<string, mixed>>
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        /** @var array<string, array<string, mixed>> $disks */
        $disks = array_replace_recursive(
            $defaults['disks'],
            is_array($overrides['disks'] ?? null) ? $overrides['disks'] : []
        );

        return [
            'default' => is_string($overrides['default'] ?? null) && $overrides['default'] !== ''
                ? $overrides['default']
                : $defaults['default'],
            'disks' => $disks,
        ];
    }
}
