<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Configurators;

use Marwa\DB\CLI\Commands\DbSeedAutoCommand;
use Marwa\DB\CLI\Commands\MakeMigrationCommand;
use Marwa\DB\CLI\Commands\MakeSeederCommand;
use Marwa\DB\CLI\Commands\MigrateCommand;
use Marwa\DB\CLI\Commands\MigrateRefreshCommand;
use Marwa\DB\CLI\Commands\MigrateRollbackCommand;
use Marwa\DB\CLI\Commands\MigrateStatusCommand;
use Marwa\DB\Seeder\SeedRunner;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Contracts\ConsoleCommandConfiguratorInterface;

final class MarwaDbConsoleConfigurator implements ConsoleCommandConfiguratorInterface
{
    public function __construct(
        private Application $app,
        private DatabaseBootstrapper $databaseBootstrapper
    ) {}

    public function registerCommands(CommandRegistry $registry): void
    {
        $manager = $this->databaseBootstrapper->bootstrap();

        if ($manager === null) {
            return;
        }

        $config = $this->databaseBootstrapper->databaseConfig();
        /** @var SeedRunner $seedRunner */
        $seedRunner = $this->app->make(SeedRunner::class);

        $registry->register(new MigrateCommand($manager, $config['migrationsPath']));
        $registry->register(new MigrateRollbackCommand($manager, $config['migrationsPath']));
        $registry->register(new MigrateRefreshCommand($manager, $config['migrationsPath']));
        $registry->register(new MigrateStatusCommand($manager, $config['migrationsPath']));
        $registry->register(new MakeMigrationCommand($config['migrationsPath']));
        $registry->register(new MakeSeederCommand($config['seedersPath'], $config['seedersNamespace']));
        $registry->register(new DbSeedAutoCommand($seedRunner));
    }
}
