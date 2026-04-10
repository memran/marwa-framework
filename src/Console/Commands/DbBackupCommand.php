<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Database\DBForge;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:backup', description: 'Backup database to a file.')]
final class DbBackupCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Backup file path', null)
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');
        $connection = $input->getOption('connection');

        $forge = $this->app()->make(DBForge::class);
        if ($connection !== 'default') {
            $forge = DBForge::create(
                $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class),
                $connection
            );
        }

        if ($path === null) {
            $timestamp = date('Y-m-d_His');
            $driver = $forge->driver();
            $ext = match ($driver) {
                'mysql', 'pgsql' => 'sql',
                'sqlite' => 'sqlite',
                default => 'sql',
            };
            $path = $this->app()->basePath('database/backups/backup_' . $timestamp . '.' . $ext);
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $forge->backup($path);
            $output->writeln("<info>Database backed up to: {$path}</info>");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to backup database: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
