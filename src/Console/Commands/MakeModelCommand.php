<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:model', description: 'Generate a framework model class for the application.')]
final class MakeModelCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Model class name, for example Post or Billing/Invoice.')
            ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Also generate a matching migration using make:migration.')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, 'Override the inferred database table name.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing model file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A model name is required.</error>');

            return Command::INVALID;
        }

        $target = $this->buildClassTarget($name, 'App\\Models', 'app/Models', 'GeneratedModel');
        $table = trim((string) $input->getOption('table'));
        $table = $table !== '' ? $table : $this->inferTableName($target['class']);

        $this->writeStubFile(
            $this->frameworkStubPath('console/model.stub'),
            $target['target'],
            [
                '{{ namespace }}' => $target['namespace'],
                '{{ class }}' => $target['class'],
                '{{ table }}' => $table,
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created model:</info> %s', $target['target']));

        if ((bool) $input->getOption('migration')) {
            $migrationApplication = $this->getApplication();

            if ($migrationApplication === null || !$migrationApplication->has('make:migration')) {
                $output->writeln('<error>The make:migration command is not available. Enable marwa-db to generate migrations.</error>');

                return Command::FAILURE;
            }

            $migrationName = 'create_' . $table . '_table';
            $migrationCommand = $migrationApplication->find('make:migration');
            $status = $migrationCommand->run(
                new ArrayInput([
                    'name' => $migrationName,
                ]),
                $output
            );

            if ($status !== Command::SUCCESS) {
                return $status;
            }
        }

        return Command::SUCCESS;
    }

    private function inferTableName(string $className): string
    {
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $className));

        return match (true) {
            str_ends_with($snake, 'y') && !preg_match('/[aeiou]y$/', $snake) => substr($snake, 0, -1) . 'ies',
            str_ends_with($snake, 's'),
            str_ends_with($snake, 'x'),
            str_ends_with($snake, 'z'),
            str_ends_with($snake, 'ch'),
            str_ends_with($snake, 'sh') => $snake . 'es',
            default => $snake . 's',
        };
    }
}
