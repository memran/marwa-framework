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
     *     lockPath: string,
     *     defaultLoopSeconds: int,
     *     defaultSleepSeconds: int
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'lockPath' => $app->basePath('storage/framework/schedule'),
            'defaultLoopSeconds' => 1,
            'defaultSleepSeconds' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     lockPath: string,
     *     defaultLoopSeconds: int,
     *     defaultSleepSeconds: int
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'lockPath' => is_string($overrides['lockPath'] ?? null) && $overrides['lockPath'] !== ''
                ? $overrides['lockPath']
                : $defaults['lockPath'],
            'defaultLoopSeconds' => max(1, (int) ($overrides['defaultLoopSeconds'] ?? $defaults['defaultLoopSeconds'])),
            'defaultSleepSeconds' => max(1, (int) ($overrides['defaultSleepSeconds'] ?? $defaults['defaultSleepSeconds'])),
        ];
    }
}
