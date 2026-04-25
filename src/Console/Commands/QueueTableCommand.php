<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\DatabaseConfig;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'queue:table', description: 'Create a migration for the queue jobs table.')]
final class QueueTableCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Override the configured queue jobs table name.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing migration file with the same timestamp and name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->config()->loadIfExists(DatabaseConfig::KEY . '.php');
        $this->config()->loadIfExists(QueueConfig::KEY . '.php');

        $database = DatabaseConfig::merge($this->app(), $this->config()->getArray(DatabaseConfig::KEY, []));
        $queue = QueueConfig::merge($this->app(), $this->config()->getArray(QueueConfig::KEY, []));
        $table = trim((string) $input->getOption('table'));
        $table = $table !== '' ? $table : $queue['database']['table'];

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            $output->writeln(sprintf('<error>Invalid queue table name [%s]. Use letters, numbers, and underscores only.</error>', $table));

            return Command::INVALID;
        }

        $timestamp = date('Y_m_d_His');
        $target = rtrim($database['migrationsPath'], DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $timestamp
            . '_create_'
            . $table
            . '_table.php';

        $this->writeStubFile(
            $this->frameworkStubPath('console/queue-jobs-migration.stub'),
            $target,
            [
                "'{{ table }}'" => var_export($table, true),
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created migration:</info> %s', $target));

        return Command::SUCCESS;
    }
}
