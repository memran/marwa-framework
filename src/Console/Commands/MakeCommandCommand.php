<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:command', description: 'Generate a Symfony console command stub for the application.')]
final class MakeCommandCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Command class name, for example CleanupCommand.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing command file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A command class name is required.</error>');

            return Command::INVALID;
        }

        $target = $this->buildClassTarget($name, 'App\\Console\\Commands', 'app/Console/Commands', 'GeneratedCommand', 'Command');
        $className = $target['class'];
        $stub = $this->frameworkStubPath('console/command.stub');
        $commandName = Str::snake(substr($className, 0, -7), '-') ?: 'generated';

        $this->writeStubFile(
            $stub,
            $target['target'],
            [
                '{{ namespace }}' => $target['namespace'],
                '{{ class }}' => $className,
                '{{ command_name }}' => 'app:' . $commandName,
            ],
            (bool) $input->getOption('force')
        );
        $output->writeln(sprintf('<info>Created console command:</info> %s', $target['target']));

        return Command::SUCCESS;
    }
}
