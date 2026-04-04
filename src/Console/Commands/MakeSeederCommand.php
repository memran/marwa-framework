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

#[AsCommand(name: 'make:seeder', description: 'Generate a database seeder class for the application.')]
final class MakeSeederCommand extends AbstractCommand
{
    public function __construct(
        private string $seedPath = '',
        private string $seedNamespace = 'Database\\Seeders'
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Seeder class name, for example UserSeeder or DatabaseSeeder.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite an existing seeder file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));

        if ($name === '') {
            $output->writeln('<error>A seeder name is required.</error>');

            return Command::INVALID;
        }

        $target = $this->resolveTarget($name);

        $this->writeStubFile(
            $this->frameworkStubPath('console/seeder.stub'),
            $target['target'],
            [
                '{{ namespace }}' => $target['namespace'],
                '{{ class }}' => $target['class'],
            ],
            (bool) $input->getOption('force')
        );

        $output->writeln(sprintf('<info>Created seeder:</info> %s', $target['target']));

        return Command::SUCCESS;
    }

    /**
     * @return array{namespace:string,class:string,target:string}
     */
    private function resolveTarget(string $name): array
    {
        $segments = preg_split('/[\\\\\/]+/', trim($name)) ?: [];
        $segments = array_values(array_filter(array_map(
            static fn (string $segment): string => preg_replace('/[^A-Za-z0-9_]/', '', $segment) ?: '',
            $segments
        )));

        if ($segments === []) {
            $segments = ['DatabaseSeeder'];
        }

        $className = (string) array_pop($segments);
        $namespace = $this->seedNamespace . ($segments !== [] ? '\\' . implode('\\', $segments) : '');
        $directory = rtrim($this->seedPath !== '' ? $this->seedPath : $this->basePath('database/seeders'), DIRECTORY_SEPARATOR);
        $target = $directory . ($segments !== [] ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments) : '') . DIRECTORY_SEPARATOR . $className . '.php';

        return [
            'namespace' => $namespace,
            'class' => $className,
            'target' => $target,
        ];
    }
}
