<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:command', description: 'Generate a Symfony console command stub for the application.')]
final class MakeCommandCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Command class name, for example CleanupCommand.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A command class name is required.</error>');

            return Command::INVALID;
        }

        $className = preg_replace('/[^A-Za-z0-9_]/', '', $name) ?: 'GeneratedCommand';
        if (!str_ends_with($className, 'Command')) {
            $className .= 'Command';
        }

        $commandName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', substr($className, 0, -7)) ?: 'generated');
        $target = $this->basePath('app/Console/Commands/' . $className . '.php');
        $stub = $this->basePath('src/Stubs/console/command.stub');

        if (!is_file($stub)) {
            $stub = dirname(__DIR__, 2) . '/Stubs/console/command.stub';
        }

        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        $contents = (string) file_get_contents($stub);
        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ command_name }}'],
            ['App\\Console\\Commands', $className, 'app:' . $commandName],
            $contents
        );

        file_put_contents($target, $contents);

        $output->writeln(sprintf('<info>Created console command:</info> %s', $target));

        return Command::SUCCESS;
    }
}
