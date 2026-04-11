<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\DB\Seeder\SeedRunner;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'module:seed', description: 'Run module database seeders')]
final class ModuleSeedCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(SeedRunner::class)) {
            $output->writeln('<comment>marwa-db is not installed. Skipping.</comment>');

            return Command::SUCCESS;
        }

        /** @var ModuleBootstrapper $moduleBootstrapper */
        $moduleBootstrapper = $this->app()->make(ModuleBootstrapper::class);
        $modulePaths = $moduleBootstrapper->seederPaths();

        if (empty($modulePaths)) {
            $output->writeln('<info>No module seeders found.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<info>Running module seeders...</info>');

        $totalSeeded = 0;

        foreach ($modulePaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $output->writeln(sprintf('<info>Seeding:</info> %s', $path));

            $runner = new SeedRunner(
                cm: $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class),
                seedPath: $path,
                seedNamespace: 'Database\\Seeders'
            );

            $runner->runAll();
            $totalSeeded++;
        }

        $output->writeln(sprintf('<info>Module seeders completed. Total: %d</info>', $totalSeeded));

        return Command::SUCCESS;
    }
}