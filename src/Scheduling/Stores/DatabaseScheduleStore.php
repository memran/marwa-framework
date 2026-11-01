<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Query\Builder;
use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Scheduling\Task;

final class DatabaseScheduleStore implements ScheduleStoreInterface
{
    /** @var array<string, string> */
    private array $owners = [];

    public function __construct(
        private DatabaseBootstrapper $databaseBootstrapper,
        private string $connection,
        private string $table
    ) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException(sprintf('Invalid scheduler table name [%s].', $table));
        }
    }

    public function acquireLock(Task $task, \DateTimeImmutable $time, int $ttlSeconds, ?int $occurrence = null): mixed
    {
        $token = bin2hex(random_bytes(16));
        $now = $time->format('Y-m-d H:i:s');
        $expires = $time->modify(sprintf('+%d seconds', max(1, $ttlSeconds)))->format('Y-m-d H:i:s');
        $query = $this->locks()->where('name', '=', $task->name())->where('expires_at', '<=', $now);
        if ($occurrence !== null) {
            $query->where(function (Builder $nested) use ($occurrence): void {
                $nested->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<', $occurrence);
            });
        }
        $values = ['token' => $token, 'expires_at' => $expires];
        if ($occurrence !== null) {
            $values['last_dispatched_at'] = $occurrence;
        }
        if ($query->update($values) !== 1) {
            try {
                $this->locks()->insert(['name' => $task->name(), 'last_dispatched_at' => $occurrence, ...$values]);
            } catch (\Throwable $error) {
                // Only a confirmed competing reservation is a normal lock miss.
                $existing = $this->locks()->where('name', '=', $task->name())->first();
                if ($existing === null) {
                    throw $error;
                }
                return null;
            }
        }
        $this->owners[$task->name()] = $token;
        try {
            $this->record($task, $time, 'running');
        } catch (\Throwable $error) {
            $this->releaseLock($task, $token);
            throw $error;
        }
        return $token;
    }

    public function adoptLock(Task $task, string $token, \DateTimeImmutable $time, bool $allowExpired = false): bool
    {
        $query = $this->locks()->where('name', '=', $task->name())->where('token', '=', $token);
        if (!$allowExpired) {
            $query->where('expires_at', '>', $time->format('Y-m-d H:i:s'));
        }
        $row = $query->first();
        if ($row === null) {
            return false;
        }
        $this->owners[$task->name()] = $token;
        return true;
    }

    public function releaseLock(Task $task, mixed $lock, bool $cancelOccurrence = false): void
    {
        if (!is_string($lock) || $lock === '') {
            return;
        }
        $values = ['token' => bin2hex(random_bytes(16)), 'expires_at' => '1970-01-01 00:00:00'];
        if ($cancelOccurrence) {
            $values['last_dispatched_at'] = null;
        }
        $this->locks()->where('name', '=', $task->name())->where('token', '=', $lock)->update($values);
        if (($this->owners[$task->name()] ?? null) === $lock) {
            unset($this->owners[$task->name()]);
        }
    }

    public function record(Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void
    {
        $name = $task->name();
        $token = $this->owners[$name] ?? null;
        $now = $time->format('Y-m-d H:i:s');
        $this->manager()->transaction(function () use ($task, $name, $token, $now, $status, $message): void {
            // A write locks this reservation row until the state update commits.
            // Skipped contenders may never write over the active owner's state.
            if ($status === 'skipped' && $this->locks()->where('name', '=', $name)->where('expires_at', '>', $now)->first() !== null) {
                return;
            }
            if ($token !== null) {
                $owned = $this->locks()->where('name', '=', $name)->where('token', '=', $token);
                if ($status !== 'failed') {
                    $owned->where('expires_at', '>', $now);
                }
                $owned->update(['token' => $token]);
                if (!$this->adoptLock($task, $token, new \DateTimeImmutable($now), $status === 'failed')) {
                    return;
                }
            } elseif ($this->locks()->where('name', '=', $name)->where('expires_at', '>', $now)->first() !== null) {
                return;
            }
            $existing = $this->states()->where('name', '=', $name)->first();
            $values = ['description' => $task->description(), 'status' => $status,
                'last_message' => $message, 'updated_at' => $now];
            $column = match ($status) {
                'running' => 'last_ran_at', 'success' => 'last_finished_at',
                'failed' => 'last_failed_at', 'skipped' => 'last_skipped_at', default => null,
            };
            if ($column !== null) {
                $values[$column] = $now;
            }
            if ($existing !== null) {
                $this->states()->where('name', '=', $name)->update($values);
            } else {
                $this->states()->insert(['name' => $name, 'created_at' => $now, ...$values]);
            }
        }, $this->connection);
    }

    private function states(): Builder
    {
        return (new Builder($this->manager(), $this->connection))->table($this->table);
    }

    private function locks(): Builder
    {
        return (new Builder($this->manager(), $this->connection))->table($this->table . '_locks');
    }

    private function manager(): ConnectionManager
    {
        return $this->databaseBootstrapper->manager()
            ?? throw new \RuntimeException('Database scheduler store requires the database bootstrapper.');
    }
}
