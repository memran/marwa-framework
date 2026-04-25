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
     *     sendmail: array{path: string},
     *     template: array{path: string, autoPlainText: bool, inlineCss: bool}
     * }
     */
    public static function defaults(Application $app): array
    {
        $appName = (string) env('APP_NAME', 'MarwaPHP');
        $driver = env('MAIL_DRIVER', env('MAIL_MAILER', 'smtp'));

        return [
            'enabled' => true,
            'driver' => self::resolveDriver($driver, 'smtp'),
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
            'template' => [
                'path' => (string) env('MAIL_TEMPLATE_PATH', 'resources/views/emails'),
                'autoPlainText' => (bool) env('MAIL_AUTO_PLAIN_TEXT', true),
                'inlineCss' => (bool) env('MAIL_INLINE_CSS', true),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
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
     *     sendmail: array{path: string},
     *     template: array{path: string, autoPlainText: bool, inlineCss: bool}
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $from = is_array($overrides['from'] ?? null) ? $overrides['from'] : [];
        $smtp = is_array($overrides['smtp'] ?? null) ? $overrides['smtp'] : [];
        $sendmail = is_array($overrides['sendmail'] ?? null) ? $overrides['sendmail'] : [];
        $template = is_array($overrides['template'] ?? null) ? $overrides['template'] : [];

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'driver' => self::resolveDriver($overrides['driver'] ?? null, $defaults['driver']),
            'charset' => is_string($overrides['charset'] ?? null) && $overrides['charset'] !== ''
                ? $overrides['charset']
                : $defaults['charset'],
            'from' => [
                'address' => is_string($from['address'] ?? null) && $from['address'] !== ''
                    ? $from['address']
                    : $defaults['from']['address'],
                'name' => is_string($from['name'] ?? null)
                    ? $from['name']
                    : $defaults['from']['name'],
            ],
            'smtp' => [
                'host' => is_string($smtp['host'] ?? null) && $smtp['host'] !== ''
                    ? $smtp['host']
                    : $defaults['smtp']['host'],
                'port' => max(1, (int) ($smtp['port'] ?? $defaults['smtp']['port'])),
                'encryption' => self::nullableString($smtp['encryption'] ?? $defaults['smtp']['encryption']),
                'username' => self::nullableString($smtp['username'] ?? $defaults['smtp']['username']),
                'password' => self::nullableString($smtp['password'] ?? $defaults['smtp']['password']),
                'authMode' => self::nullableString($smtp['authMode'] ?? $defaults['smtp']['authMode']),
                'timeout' => max(1, (int) ($smtp['timeout'] ?? $defaults['smtp']['timeout'])),
            ],
            'sendmail' => [
                'path' => is_string($sendmail['path'] ?? null) && $sendmail['path'] !== ''
                    ? $sendmail['path']
                    : $defaults['sendmail']['path'],
            ],
            'template' => [
                'path' => is_string($template['path'] ?? null) && $template['path'] !== ''
                    ? $template['path']
                    : $defaults['template']['path'],
                'autoPlainText' => (bool) ($template['autoPlainText'] ?? $defaults['template']['autoPlainText']),
                'inlineCss' => (bool) ($template['inlineCss'] ?? $defaults['template']['inlineCss']),
            ],
        ];
    }

    private static function resolveDriver(mixed $driver, string $default): string
    {
        if (!is_string($driver) || $driver === '') {
            return $default;
        }

        $driver = strtolower(trim($driver));

        if (!in_array($driver, ['smtp', 'sendmail', 'mail'], true)) {
            return $default;
        }

        return $driver;
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
