<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Schema\Builder as SchemaBuilder;
use Marwa\DB\Schema\MigrationRepository;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\Console\AbstractCommand;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'migrate:refresh',
    description: 'Rollback and re-run application and module migrations',
)]
final class MigrateRefreshCommand extends AbstractCommand
{
    public function __construct(
        private readonly ConnectionManager $manager,
        private readonly string $applicationPath,
        private readonly ModuleBootstrapper $modules,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        SchemaBuilder::useConnectionManager($this->manager);
        $paths = $this->migrationPaths();
        $down = $this->rollbackAll($paths);
        $up = $this->migrateAll($paths);

        $output->writeln(sprintf(
            '<comment>Refreshed application and module migrations. Down: %d, Up: %d</comment>',
            $down,
            $up,
        ));

        return Command::SUCCESS;
    }

    /** @return list<string> */
    private function migrationPaths(): array
    {
        $modulePaths = array_filter(
            $this->modules->migrationPaths(),
            static fn (string $path): bool => is_dir($path),
        );

        return array_values(array_unique([$this->applicationPath, ...$modulePaths]));
    }

    /** @param list<string> $paths */
    private function rollbackAll(array $paths): int
    {
        if ($paths === []) {
            return 0;
        }

        $files = $this->migrationFileMap($paths);
        $repository = $this->repository($paths[0]);
        $this->assertMigrationFilesAvailable($repository, $files);
        $rolledBack = 0;

        while (($batch = $this->lastBatch($repository)) !== []) {
            $rolledBack += $this->rollbackBatch($batch, $files);
        }

        return $rolledBack;
    }

    /** @param array<string, string> $files */
    private function assertMigrationFilesAvailable(MigrationRepository $repository, array $files): void
    {
        foreach (array_keys($repository->getRanWithDetails()) as $migration) {
            if (!isset($files[$migration])) {
                throw new RuntimeException(sprintf(
                    'Cannot refresh because migration [%s] is no longer available.',
                    $migration,
                ));
            }
        }
    }

    /**
     * @param list<string> $batch
     * @param array<string, string> $files
     */
    private function rollbackBatch(array $batch, array $files): int
    {
        $paths = [];
        foreach (array_reverse($batch) as $migration) {
            if (!isset($files[$migration])) {
                throw new RuntimeException(sprintf(
                    'Cannot refresh because migration [%s] is no longer available.',
                    $migration,
                ));
            }
            $paths[$files[$migration]] = true;
        }

        $rolledBack = 0;
        foreach (array_keys($paths) as $path) {
            $rolledBack += $this->repository($path)->rollbackLastBatch();
        }

        if ($rolledBack === 0) {
            throw new RuntimeException('Unable to roll back the latest migration batch.');
        }

        return $rolledBack;
    }

    /** @param list<string> $paths */
    private function migrateAll(array $paths): int
    {
        $migrated = 0;
        foreach ($paths as $path) {
            $migrated += $this->repository($path)->migrate();
        }

        return $migrated;
    }

    /**
     * @param list<string> $paths
     * @return array<string, string>
     */
    private function migrationFileMap(array $paths): array
    {
        $map = [];
        foreach ($paths as $path) {
            foreach ($this->repository($path)->getMigrationFiles() as $migration) {
                if (isset($map[$migration]) && $map[$migration] !== $path) {
                    throw new RuntimeException(sprintf(
                        'Duplicate migration name [%s] exists in multiple migration directories.',
                        $migration,
                    ));
                }
                $map[$migration] = $path;
            }
        }

        return $map;
    }

    /** @return list<string> */
    private function lastBatch(MigrationRepository $repository): array
    {
        $details = $repository->getRanWithDetails();
        $lastBatch = array_reduce(
            $details,
            static fn (int $batch, array $detail): int => max($batch, $detail['batch']),
            0,
        );

        return array_keys(array_filter(
            $details,
            static fn (array $detail): bool => $detail['batch'] === $lastBatch && $lastBatch > 0,
        ));
    }

    private function repository(string $path): MigrationRepository
    {
        return new MigrationRepository($this->manager->getPdo(), $path);
    }
}
