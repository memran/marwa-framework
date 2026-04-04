<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class MailConfig
{
    public const KEY = 'mail';

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     charset: string,
     *     from: array{address: string, name: string},
     *     smtp: array{
     *         host: string,
     *         port: int,
     *         encryption: string|null,
     *         username: string|null,
     *         password: string|null,
     *         authMode: string|null,
     *         timeout: int
     *     },
     *     sendmail: array{path: string}
     * }
     */
    public static function defaults(Application $app): array
    {
        $appName = (string) env('APP_NAME', 'MarwaPHP');

        return [
            'enabled' => true,
            'driver' => (string) env('MAIL_DRIVER', 'smtp'),
            'charset' => (string) env('MAIL_CHARSET', 'UTF-8'),
            'from' => [
                'address' => (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
                'name' => (string) env('MAIL_FROM_NAME', $appName !== '' ? $appName : 'MarwaPHP'),
            ],
            'smtp' => [
                'host' => (string) env('MAIL_HOST', '127.0.0.1'),
                'port' => (int) env('MAIL_PORT', 1025),
                'encryption' => self::nullableString(env('MAIL_ENCRYPTION', null)),
                'username' => self::nullableString(env('MAIL_USERNAME', null)),
                'password' => self::nullableString(env('MAIL_PASSWORD', null)),
                'authMode' => self::nullableString(env('MAIL_AUTH_MODE', null)),
                'timeout' => (int) env('MAIL_TIMEOUT', 30),
            ],
            'sendmail' => [
                'path' => (string) env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs'),
            ],
        ];
    }

    /**
     * @param mixed $value
     */
    private static function nullableString(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
