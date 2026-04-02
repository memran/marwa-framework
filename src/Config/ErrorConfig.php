<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\ErrorHandler\Support\FallbackRenderer;
use Marwa\Framework\Application;

final class ErrorConfig
{
    public const KEY = 'error';

    /**
     * @return array{
     *     enabled: bool,
     *     appName: string,
     *     environment: string,
     *     useLogger: bool,
     *     useDebugReporter: bool,
     *     renderer: class-string|null
     * }
     */
    public static function defaults(Application $app): array
    {
        $appName = env('APP_NAME', 'MarwaPHP');
        $environment = env('APP_ENV', 'production');

        return [
            'enabled' => true,
            'appName' => is_string($appName) && $appName !== '' ? $appName : 'MarwaPHP',
            'environment' => is_string($environment) && $environment !== '' ? $environment : 'production',
            'useLogger' => true,
            'useDebugReporter' => true,
            'renderer' => FallbackRenderer::class,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     enabled: bool,
     *     appName: string,
     *     environment: string,
     *     useLogger: bool,
     *     useDebugReporter: bool,
     *     renderer: class-string|null
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);
        $renderer = $overrides['renderer'] ?? $defaults['renderer'];

        return [
            'enabled' => (bool) ($overrides['enabled'] ?? $defaults['enabled']),
            'appName' => is_string($overrides['appName'] ?? null) && $overrides['appName'] !== ''
                ? $overrides['appName']
                : $defaults['appName'],
            'environment' => is_string($overrides['environment'] ?? null) && $overrides['environment'] !== ''
                ? $overrides['environment']
                : $defaults['environment'],
            'useLogger' => (bool) ($overrides['useLogger'] ?? $defaults['useLogger']),
            'useDebugReporter' => (bool) ($overrides['useDebugReporter'] ?? $defaults['useDebugReporter']),
            'renderer' => is_string($renderer) && $renderer !== '' ? $renderer : $defaults['renderer'],
        ];
    }
}
