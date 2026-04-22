<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class SessionConfig
{
    public const KEY = 'session';

    /**
     * @return array{
     *     enabled: bool,
     *     autoStart: bool,
     *     name: string,
     *     lifetime: int,
     *     path: string,
     *     domain: string,
     *     secure: bool,
     *     httpOnly: bool,
     *     sameSite: string,
     *     encrypt: bool,
     *     savePath: string
     * }
     */
    public static function defaults(Application $app): array
    {
        $environment = (string) env('APP_ENV', 'production');

        return [
            'enabled' => true,
            'autoStart' => false,
            'name' => 'marwa_session',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => in_array($environment, ['production', 'staging'], true),
            'httpOnly' => true,
            'sameSite' => 'Lax',
            'encrypt' => true,
            'savePath' => storage_path('session'),
        ];
    }
}
