<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\DB\Seeder\SeedRunner;
use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:seed', description: 'Run application seeders with Faker-friendly support.')]
final class SeedRunCommand extends AbstractCommand
{
    public function __construct(
        private SeedRunner $runner,
        private DatabaseBootstrapper $databaseBootstrapper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('class', InputArgument::OPTIONAL, 'Seeder class name or FQCN to run directly.')
            ->addOption('only', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Run only specific seeders by short class name.', [])
            ->addOption('except', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Skip specific seeders by short class name.', [])
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List seeders without executing them.')
            ->addOption('no-transaction', null, InputOption::VALUE_NONE, 'Do not wrap seeding in a transaction.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $class = trim((string) $input->getArgument('class'));
        $only = $this->normalizeList($input->getOption('only'));
        $except = $this->normalizeList($input->getOption('except'));
        $dryRun = (bool) $input->getOption('dry-run');
        $transaction = !$input->getOption('no-transaction');

        if ($class !== '') {
            $this->runner->discoverSeeders();
            $fqcn = $this->resolveSeederClass($class);

            if ($dryRun) {
                $output->writeln('<info>Seeder detected:</info> ' . $fqcn);

                return Command::SUCCESS;
            }

            $this->runner->runOne($fqcn, $transaction);
            $output->writeln('<info>Seeder finished:</info> ' . $fqcn);

            return Command::SUCCESS;
        }

        $classes = $this->runner->discoverSeeders();

        if ($only !== []) {
            $classes = array_values(array_filter($classes, static fn (string $fqcn): bool => in_array(self::short($fqcn), $only, true)));
        }

        if ($except !== []) {
            $classes = array_values(array_filter($classes, static fn (string $fqcn): bool => !in_array(self::short($fqcn), $except, true)));
        }

        sort($classes, SORT_STRING);

        if ($classes === []) {
            $output->writeln('<comment>No seeders found.</comment>');

            return Command::SUCCESS;
        }

        $output->writeln('<info>Seeders detected:</info>');
        foreach ($classes as $seeder) {
            $output->writeln('  - ' . $seeder);
        }

        if ($dryRun) {
            $output->writeln('<comment>Dry run. Nothing executed.</comment>');

            return Command::SUCCESS;
        }

        $this->runner->runAll($transaction, $only !== [] ? $only : null, $except);
        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }

    /**
     * @param array<int, mixed> $input
     * @return array<int, string>
     */
    private function normalizeList(array $input): array
    {
        $values = [];

        foreach ($input as $item) {
            foreach (array_map('trim', explode(',', (string) $item)) as $value) {
                if ($value !== '') {
                    $values[] = $value;
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function resolveSeederClass(string $class): string
    {
        if (class_exists($class)) {
            return $class;
        }

        $namespace = rtrim($this->databaseBootstrapper->databaseConfig()['seedersNamespace'], '\\');
        $candidate = $namespace . '\\' . ltrim($class, '\\');

        return class_exists($candidate) ? $candidate : $class;
    }

    private static function short(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}
