<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Database\DBForge;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:restore', description: 'Restore database from a backup file.')]
final class DbRestoreCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputOption::VALUE_REQUIRED, 'Backup file path')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $connection = $input->getOption('connection');

        $forge = $this->app()->make(DBForge::class);
        if ($connection !== 'default') {
            $forge = DBForge::create(
                $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class),
                $connection
            );
        }

        if (!file_exists($path)) {
            $output->writeln("<error>Backup file not found: {$path}</error>");
            return Command::FAILURE;
        }

        try {
            $forge->restore($path);
            $output->writeln("<info>Database restored from: {$path}</info>");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to restore database: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}