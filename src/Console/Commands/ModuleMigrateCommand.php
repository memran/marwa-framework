<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\DB\Schema\MigrationRepository;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'module:migrate', description: 'Run module database migrations')]
final class ModuleMigrateCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(MigrationRepository::class)) {
            $output->writeln('<comment>marwa-db is not installed. Skipping.</comment>');

            return Command::SUCCESS;
        }

        /** @var ModuleBootstrapper $moduleBootstrapper */
        $moduleBootstrapper = $this->app()->make(ModuleBootstrapper::class);
        $modulePaths = $moduleBootstrapper->migrationPaths();

        if (empty($modulePaths)) {
            $output->writeln('<info>No module migrations found.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<info>Running module migrations...</info>');

        $manager = $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class);

        $totalMigrated = 0;

        foreach ($modulePaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $output->writeln(sprintf('<info>Migrating:</info> %s', $path));

            $repo = new MigrationRepository($manager->getPdo(), $path);
            $count = $repo->migrate();
            $totalMigrated += $count;
        }

        $output->writeln(sprintf('<info>Module migrations completed. Total: %d</info>', $totalMigrated));

        return Command::SUCCESS;
    }
}