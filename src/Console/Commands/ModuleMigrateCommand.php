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
            if (is_dir($path)) {
                $output->writeln(sprintf('<info>Migrating directory:</info> %s', $path));

                $repo = new MigrationRepository($manager->getPdo(), $path);
                $count = $repo->migrate();
                $totalMigrated += $count;
            } elseif (is_file($path)) {
                $output->writeln(sprintf('<info>Migrating file:</info> %s', $path));

                $count = $this->migrateSingleFile($manager, $path);
                $totalMigrated += $count;
            }
        }

        $output->writeln(sprintf('<info>Module migrations completed. Total: %d</info>', $totalMigrated));

        return Command::SUCCESS;
    }

    private function migrateSingleFile(\Marwa\DB\Connection\ConnectionManager $manager, string $file): int
    {
        $pdo = $manager->getPdo();

        require_once $file;

        $className = $this->extractMigrationClassName($file);

        if ($className === null) {
            return 0;
        }

        $migration = new $className();

        if (method_exists($migration, 'up')) {
            $migration->up();
            return 1;
        }

        return 0;
    }

    private function extractMigrationClassName(string $file): ?string
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        if (preg_match('/class\s+(\w+)\s+extends/i', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
