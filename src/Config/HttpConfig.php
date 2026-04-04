<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class HttpConfig
{
    public const KEY = 'http';

    /**
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     clients: array<string, array<string, mixed>>
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'default' => 'default',
            'clients' => [
                'default' => self::defaultClient(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     clients: array<string, array<string, mixed>>
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $clients = $defaults['clients'];
        $defaultName = $defaults['default'];

        if (is_array($overrides['clients'] ?? null)) {
            foreach ($overrides['clients'] as $name => $client) {
                if (!is_string($name) || $name === '' || !is_array($client)) {
                    continue;
                }

                $clients[$name] = self::normalizeClient(array_replace_recursive(self::defaultClient(), $client));
            }
        }

        if (!isset($clients[$defaultName])) {
            $clients[$defaultName] = self::defaultClient();
        }

        $configuredDefault = is_string($overrides['default'] ?? null) && $overrides['default'] !== ''
            ? $overrides['default']
            : $defaultName;

        if (!isset($clients[$configuredDefault])) {
            $clients[$configuredDefault] = self::defaultClient();
        }

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'default' => $configuredDefault,
            'clients' => $clients,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultClient(): array
    {
        return [
            'base_uri' => null,
            'timeout' => 30.0,
            'connect_timeout' => 10.0,
            'http_errors' => false,
            'verify' => true,
            'headers' => [],
        ];
    }

    /**
     * @param array<string, mixed> $client
     * @return array<string, mixed>
     */
    private static function normalizeClient(array $client): array
    {
        $client['timeout'] = (float) ($client['timeout'] ?? 30.0);
        $client['connect_timeout'] = (float) ($client['connect_timeout'] ?? 10.0);
        $client['http_errors'] = (bool) ($client['http_errors'] ?? false);

        if (!is_bool($client['verify'] ?? null) && !is_string($client['verify'] ?? null)) {
            $client['verify'] = true;
        }

        if (!is_array($client['headers'] ?? null)) {
            $client['headers'] = [];
        }

        return array_filter(
            $client,
            static fn (mixed $value): bool => $value !== null
        );
    }
}
