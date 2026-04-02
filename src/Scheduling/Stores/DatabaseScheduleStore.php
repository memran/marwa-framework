<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Scheduling\Task;

final class DatabaseScheduleStore implements ScheduleStoreInterface
{
    public function __construct(
        private DatabaseBootstrapper $databaseBootstrapper,
        private string $connection,
        private string $table
    ) {}

    public function acquireLock(Task $task, \DateTimeImmutable $time, int $ttlSeconds): mixed
    {
        $manager = $this->manager();
        $pdo = $manager->getPdo($this->connection);
        $expiresAt = $time->modify(sprintf('+%d seconds', $ttlSeconds))->format('Y-m-d H:i:s');
        $now = $time->format('Y-m-d H:i:s');

        return $manager->transaction(function () use ($pdo, $task, $expiresAt, $now): ?string {
            $existing = $this->find($pdo, $task->name());

            if (is_array($existing) && is_string($existing['lock_expires_at'] ?? null) && $existing['lock_expires_at'] > $now) {
                return null;
            }

            if ($existing !== null) {
                $statement = $pdo->prepare(sprintf(
                    'UPDATE %s SET description = :description, status = :status, lock_expires_at = :lock_expires_at, updated_at = :updated_at WHERE name = :name',
                    $this->table
                ));

                $statement->execute([
                    'description' => $task->description(),
                    'status' => 'running',
                    'lock_expires_at' => $expiresAt,
                    'updated_at' => $now,
                    'name' => $task->name(),
                ]);
            } else {
                $statement = $pdo->prepare(sprintf(
                    'INSERT INTO %s (name, description, status, lock_expires_at, created_at, updated_at) VALUES (:name, :description, :status, :lock_expires_at, :created_at, :updated_at)',
                    $this->table
                ));

                $statement->execute([
                    'name' => $task->name(),
                    'description' => $task->description(),
                    'status' => 'running',
                    'lock_expires_at' => $expiresAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $task->name();
        }, $this->connection);
    }

    public function releaseLock(Task $task, mixed $lock): void
    {
        if (!is_string($lock) || $lock === '') {
            return;
        }

        $statement = $this->manager()->getPdo($this->connection)->prepare(sprintf(
            'UPDATE %s SET lock_expires_at = NULL, updated_at = :updated_at WHERE name = :name',
            $this->table
        ));

        $statement->execute([
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'name' => $task->name(),
        ]);
    }

    public function record(Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void
    {
        $pdo = $this->manager()->getPdo($this->connection);
        $now = $time->format('Y-m-d H:i:s');
        $existing = $this->find($pdo, $task->name());

        $payload = [
            'description' => $task->description(),
            'status' => $status,
            'last_message' => $message,
            'lock_expires_at' => null,
            'updated_at' => $now,
            'last_ran_at' => $status === 'success' ? $now : ($existing['last_ran_at'] ?? null),
            'last_finished_at' => $status === 'success' ? $now : ($existing['last_finished_at'] ?? null),
            'last_failed_at' => $status === 'failed' ? $now : ($existing['last_failed_at'] ?? null),
            'last_skipped_at' => $status === 'skipped' ? $now : ($existing['last_skipped_at'] ?? null),
        ];

        if ($existing !== null) {
            $statement = $pdo->prepare(sprintf(
                'UPDATE %s SET description = :description, status = :status, last_message = :last_message, lock_expires_at = :lock_expires_at, last_ran_at = :last_ran_at, last_finished_at = :last_finished_at, last_failed_at = :last_failed_at, last_skipped_at = :last_skipped_at, updated_at = :updated_at WHERE name = :name',
                $this->table
            ));

            $statement->execute([...$payload, 'name' => $task->name()]);

            return;
        }

        $statement = $pdo->prepare(sprintf(
            'INSERT INTO %s (name, description, status, last_message, lock_expires_at, last_ran_at, last_finished_at, last_failed_at, last_skipped_at, created_at, updated_at) VALUES (:name, :description, :status, :last_message, :lock_expires_at, :last_ran_at, :last_finished_at, :last_failed_at, :last_skipped_at, :created_at, :updated_at)',
            $this->table
        ));

        $statement->execute([
            ...$payload,
            'name' => $task->name(),
            'created_at' => $now,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function find(\PDO $pdo, string $name): ?array
    {
        $statement = $pdo->prepare(sprintf('SELECT * FROM %s WHERE name = :name LIMIT 1', $this->table));
        $statement->execute(['name' => $name]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function manager(): ConnectionManager
    {
        $manager = $this->databaseBootstrapper->manager();

        if (!$manager instanceof ConnectionManager) {
            throw new \RuntimeException('Database scheduler store requires the database bootstrapper to be enabled.');
        }

        return $manager;
    }
}
