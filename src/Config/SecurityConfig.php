<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class SecurityConfig
{
    public const KEY = 'security';

    /**
     * @return array{
     *     enabled: bool,
     *     csrf: array{
     *         enabled: bool,
     *         field: string,
     *         header: string,
     *         token: string,
     *         methods: list<string>,
     *         except: list<string>
     *     },
     *     trustedHosts: list<string>,
     *     trustedOrigins: list<string>,
     *     throttle: array{
     *         enabled: bool,
     *         prefix: string,
     *         limit: int,
     *         window: int
     *     },
     *     risk: array{
     *         enabled: bool,
     *         logPath: string,
     *         pruneAfterDays: int,
     *         topCount: int
     *     }
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => true,
            'csrf' => [
                'enabled' => true,
                'field' => '_token',
                'header' => 'X-CSRF-TOKEN',
                'token' => '__marwa_csrf_token',
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                'except' => [],
            ],
            'trustedHosts' => self::trustedHosts(),
            'trustedOrigins' => self::trustedOrigins(),
            'throttle' => [
                'enabled' => true,
                'prefix' => 'security',
                'limit' => 60,
                'window' => 60,
            ],
            'risk' => [
                'enabled' => true,
                'logPath' => storage_path('security/risk.jsonl'),
                'pruneAfterDays' => 30,
                'topCount' => 10,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private static function trustedHosts(): array
    {
        $configured = self::stringList(env('SECURITY_TRUSTED_HOSTS', ''));

        if ($configured !== []) {
            return $configured;
        }

        $host = self::appUrlPart('host');

        return $host === null ? [] : [$host];
    }

    /**
     * @return list<string>
     */
    private static function trustedOrigins(): array
    {
        $configured = self::stringList(env('SECURITY_TRUSTED_ORIGINS', ''));

        if ($configured !== []) {
            return $configured;
        }

        $scheme = self::appUrlPart('scheme');
        $host = self::appUrlPart('host');

        if ($scheme === null || $host === null) {
            return [];
        }

        $origin = $scheme . '://' . $host;
        $port = self::appUrlPart('port');

        return [$port === null ? $origin : $origin . ':' . $port];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
    }

    private static function appUrlPart(string $part): ?string
    {
        $url = env('APP_URL');

        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));

        if (!is_array($parts) || !isset($parts[$part])) {
            return null;
        }

        return (string) $parts[$part];
    }
}
