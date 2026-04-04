<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\KafkaPublisherInterface;

final class NotificationConfig
{
    public const KEY = 'notification';

    /**
     * @return array{
     *     enabled: bool,
     *     default: list<string>,
     *     channels: array<string, array<string, mixed>>
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'default' => ['mail'],
            'channels' => [
                'mail' => [
                    'enabled' => true,
                ],
                'database' => [
                    'enabled' => true,
                    'connection' => 'default',
                    'table' => 'notifications',
                ],
                'http' => [
                    'enabled' => true,
                    'client' => 'default',
                    'method' => 'POST',
                    'url' => null,
                    'headers' => [],
                ],
                'sms' => [
                    'enabled' => false,
                    'client' => 'default',
                    'method' => 'POST',
                    'url' => null,
                    'headers' => [],
                ],
                'kafka' => [
                    'enabled' => false,
                    'publisher' => KafkaPublisherInterface::class,
                    'topic' => 'notifications',
                    'key' => null,
                    'headers' => [],
                    'options' => [],
                ],
                'broadcast' => [
                    'enabled' => true,
                    'event' => \Marwa\Framework\Notifications\Events\NotificationBroadcasted::class,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     default: list<string>,
     *     channels: array<string, array<string, mixed>>
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $channels = $defaults['channels'];

        if (is_array($overrides['channels'] ?? null)) {
            foreach ($overrides['channels'] as $name => $channel) {
                if (!is_string($name) || $name === '' || !is_array($channel)) {
                    continue;
                }

                $channels[$name] = array_replace_recursive($channels[$name] ?? [], $channel);
            }
        }

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'default' => array_values(array_filter(
                is_array($overrides['default'] ?? null) ? $overrides['default'] : $defaults['default'],
                static fn (mixed $channel): bool => is_string($channel) && $channel !== ''
            )),
            'channels' => $channels,
        ];
    }
}
