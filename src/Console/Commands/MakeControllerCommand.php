<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:controller', description: 'Generate a controller class for the application.')]
final class MakeControllerCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Controller class name, for example PostController or Admin/PostController.')
            ->addOption('resource', null, InputOption::VALUE_NONE, 'Generate a resource-style controller with CRUD methods.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing controller file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A controller name is required.</error>');

            return Command::INVALID;
        }

        $target = $this->buildClassTarget($name, 'App\\Http\\Controllers', 'app/Http/Controllers', 'GeneratedController', 'Controller');
        $stub = $this->frameworkStubPath((bool) $input->getOption('resource')
            ? 'console/controller-resource.stub'
            : 'console/controller.stub');

        $this->writeStubFile(
            $stub,
            $target['target'],
            [
                '{{ namespace }}' => $target['namespace'],
                '{{ class }}' => $target['class'],
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created controller:</info> %s', $target['target']));

        return Command::SUCCESS;
    }
}
