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

#[AsCommand(name: 'db:drop', description: 'Drop a database.')]
final class DbDropCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Database name')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default')
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force drop without confirmation', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $connection = $input->getOption('connection');
        $force = $input->getOption('force');

        $forge = $this->app()->make(DBForge::class);
        if ($connection !== 'default') {
            $forge = DBForge::create(
                $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class),
                $connection
            );
        }

        try {
            if (!$force) {
                $output->writeln("<comment>Dropping database '{$name}' cannot be undone!</comment>");
            }
            $forge->dropDatabase($name);
            $output->writeln("<info>Database '{$name}' dropped successfully.</info>");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to drop database: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
