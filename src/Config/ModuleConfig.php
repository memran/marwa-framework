<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;

final class ModuleConfig
{
    public const KEY = 'module';

    /**
     * @return array{
     *     enabled: bool,
     *     paths: list<string>,
     *     cache: string,
     *     forceRefresh: bool,
     *     commandPaths: list<string>,
     *     commandConventions: list<string>
     * }
     */
    public static function defaults(Application $app): array
    {
        return [
            'enabled' => false,
            'paths' => [
                $app->basePath('modules'),
            ],
            'cache' => BootstrapConfig::defaults($app)['moduleCache'],
            'forceRefresh' => false,
            'commandPaths' => [
                'commands',
            ],
            'commandConventions' => [
                'Console/Commands',
                'src/Console/Commands',
            ],
        ];
    }
}
