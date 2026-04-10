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

#[AsCommand(name: 'db:optimize', description: 'Optimize all tables in the database.')]
final class DbOptimizeCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $input->getOption('connection');

        $forge = $this->app()->make(DBForge::class);
        if ($connection !== 'default') {
            $forge = DBForge::create(
                $this->app()->make(\Marwa\DB\Connection\ConnectionManager::class),
                $connection
            );
        }

        try {
            $forge->optimize();
            $output->writeln('<info>Database optimized successfully.</info>');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to optimize database: {$e->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}