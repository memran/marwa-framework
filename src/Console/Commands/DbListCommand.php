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

#[AsCommand(name: 'db:list', description: 'List databases or tables.')]
final class DbListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('tables', 't', InputOption::VALUE_NONE, 'List tables instead of databases')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $input->getOption('tables');
        $connection = $input->getOption('connection');

        $forge = $this->app()->make(DBForge::class);
        if ($connection !== 'default') {
            $forge = DBForge::create(
                $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class),
                $connection
            );
        }

        try {
            if ($tables) {
                $items = $forge->listTables();
                $output->writeln('<comment>Tables:</comment>');
            } else {
                $items = $forge->listDatabases();
                $output->writeln('<comment>Databases:</comment>');
            }

            if (empty($items)) {
                $output->writeln('  (none)');
            } else {
                foreach ($items as $item) {
                    $output->writeln('  - ' . $item);
                }
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to list: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}