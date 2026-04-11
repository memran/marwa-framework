<?php

declare(strict_types=1);

namespace Marwa\Framework\Config;

use Marwa\Framework\Application;
use Marwa\Framework\Console\Commands\BootstrapCacheCommand;
use Marwa\Framework\Console\Commands\BootstrapClearCommand;
use Marwa\Framework\Console\Commands\CacheClearCommand;
use Marwa\Framework\Console\Commands\ConfigCacheCommand;
use Marwa\Framework\Console\Commands\ConfigClearCommand;
use Marwa\Framework\Console\Commands\GenerateKeyCommand;
use Marwa\Framework\Console\Commands\KafkaConsumeCommand;
use Marwa\Framework\Console\Commands\MakeAiHelperCommand;
use Marwa\Framework\Console\Commands\MakeCommandCommand;
use Marwa\Framework\Console\Commands\MakeControllerCommand;
use Marwa\Framework\Console\Commands\MakeMailCommand;
use Marwa\Framework\Console\Commands\MakeModelCommand;
use Marwa\Framework\Console\Commands\MakeModuleCommand;
use Marwa\Framework\Console\Commands\MakeThemeCommand;
use Marwa\Framework\Console\Commands\ModuleCacheCommand;
use Marwa\Framework\Console\Commands\ModuleClearCommand;
use Marwa\Framework\Console\Commands\ModuleMigrateCommand;
use Marwa\Framework\Console\Commands\RouteCacheCommand;
use Marwa\Framework\Console\Commands\RouteClearCommand;
use Marwa\Framework\Console\Commands\ScheduleRunCommand;
use Marwa\Framework\Console\Commands\ScheduleTableCommand;
use Marwa\Framework\Console\Commands\SecurityReportCommand;
use Marwa\Framework\Console\Commands\ShellCommand;

final class ConsoleConfig
{
    public const KEY = 'console';

    /**
     * @return array{
     *     name: string,
     *     version: string,
     *     commands: list<class-string>,
     *     discover: list<array{namespace?:string,path?:string,optional?:bool}>,
     *     autoDiscover: list<array{namespace?:string,path?:string,optional?:bool}>,
     *     stubsPath: string
     * }
     */
    public static function defaults(Application $app): array
    {
        $appName = env('APP_NAME', 'Marwa Console');
        $appVersion = env('APP_VERSION', 'dev');

        return [
            'name' => is_string($appName) && $appName !== '' ? $appName : 'Marwa Console',
            'version' => is_string($appVersion) && $appVersion !== '' ? $appVersion : 'dev',
            'commands' => [
                BootstrapCacheCommand::class,
                BootstrapClearCommand::class,
                CacheClearCommand::class,
                ConfigCacheCommand::class,
                ConfigClearCommand::class,
                GenerateKeyCommand::class,
                ScheduleRunCommand::class,
                ScheduleTableCommand::class,
                RouteCacheCommand::class,
                RouteClearCommand::class,
                SecurityReportCommand::class,
                ModuleCacheCommand::class,
                ModuleClearCommand::class,
                ModuleMigrateCommand::class,
                MakeCommandCommand::class,
                MakeControllerCommand::class,
                MakeMailCommand::class,
                MakeModelCommand::class,
                MakeModuleCommand::class,
                MakeThemeCommand::class,
                MakeAiHelperCommand::class,
                KafkaConsumeCommand::class,
                ShellCommand::class,
            ],
            'discover' => [],
            'autoDiscover' => [
                [
                    'namespace' => 'Marwa\\Db\\Console\\Commands',
                    'optional' => true,
                ],
            ],
            'stubsPath' => dirname(__DIR__) . '/Stubs/ai',
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{
     *     name: string,
     *     version: string,
     *     commands: list<class-string>,
     *     discover: list<array{namespace?:string,path?:string,optional?:bool}>,
     *     autoDiscover: list<array{namespace?:string,path?:string,optional?:bool}>,
     *     stubsPath: string
     * }
     */
    public static function merge(Application $app, array $overrides): array
    {
        $defaults = self::defaults($app);

        return [
            'name' => is_string($overrides['name'] ?? null) && $overrides['name'] !== '' ? $overrides['name'] : $defaults['name'],
            'version' => is_string($overrides['version'] ?? null) && $overrides['version'] !== '' ? $overrides['version'] : $defaults['version'],
            'commands' => [
                ...$defaults['commands'],
                ...array_values(array_filter($overrides['commands'] ?? [], static fn (mixed $command): bool => is_string($command) && $command !== '')),
            ],
            'discover' => [
                ...$defaults['discover'],
                ...array_values(array_filter($overrides['discover'] ?? [], 'is_array')),
            ],
            'autoDiscover' => [
                ...$defaults['autoDiscover'],
                ...array_values(array_filter($overrides['autoDiscover'] ?? [], 'is_array')),
            ],
            'stubsPath' => is_string($overrides['stubsPath'] ?? null) && $overrides['stubsPath'] !== ''
                ? $overrides['stubsPath']
                : $defaults['stubsPath'],
        ];
    }
}
