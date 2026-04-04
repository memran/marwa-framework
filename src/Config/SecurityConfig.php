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
                'enabled' => false,
                'field' => '_token',
                'header' => 'X-CSRF-TOKEN',
                'token' => '__marwa_csrf_token',
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                'except' => [],
            ],
            'trustedHosts' => [],
            'trustedOrigins' => [],
            'throttle' => [
                'enabled' => false,
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
}
