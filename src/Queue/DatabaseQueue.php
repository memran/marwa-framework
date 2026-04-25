<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use DateTimeImmutable;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Facades\DB;
use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class DatabaseQueue implements QueueInterface
{
    /**
     * @var array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int,
     *     tries: int|null
     * }|null
     */
    private ?array $queueConfig = null;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger,
        private ConnectionManager $manager
    ) {}

    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        $config = $this->configuration();

        if (!$config['enabled']) {
            throw new \RuntimeException('Queue support is disabled.');
        }

        $queueName = $queue !== null && $queue !== '' ? $queue : $config['default'];
        $job = new QueuedJob(
            id: bin2hex(random_bytes(16)),
            name: $name,
            queue: $queueName,
            payload: $payload,
            attempts: 0,
            availableAt: time() + max(0, $delaySeconds),
            createdAt: time()
        );

        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        DB::table($table, $connection)->insert([
            'id' => $job->id(),
            'name' => $job->name(),
            'queue' => $job->queue(),
            'payload' => json_encode($job->payload(), JSON_THROW_ON_ERROR),
            'attempts' => $job->attempts(),
            'available_at' => $job->availableAt(),
            'reserved_at' => null,
            'reserved_by' => null,
            'completed_at' => null,
            'failed_at' => null,
            'created_at' => $job->createdAt(),
            'updated_at' => $job->createdAt(),
        ]);

        $this->logger->info('Job queued', [
            'id' => $job->id(),
            'name' => $job->name(),
            'queue' => $job->queue(),
            'available_at' => $job->availableAt(),
        ]);

        return $job;
    }

    public function pushAt(string $name, int $timestamp, array $payload = [], ?string $queue = null): QueuedJob
    {
        return $this->push($name, $payload, $queue, $timestamp - time());
    }

    public function pushRecurring(string $name, array $schedule, array $payload = [], ?string $queue = null): QueuedJob
    {
        $payload['_recurring'] = true;
        $payload['_schedule'] = $schedule;

        return $this->push($name, $payload, $queue);
    }

    public function pop(?string $queue = null, ?DateTimeImmutable $now = null): ?QueuedJob
    {
        $config = $this->configuration();

        if (!$config['enabled']) {
            return null;
        }

        $queueName = $queue !== null && $queue !== '' ? $queue : $config['default'];
        $timestamp = ($now ?? new DateTimeImmutable())->getTimestamp();
        $workerId = uniqid('worker:', true);
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        return $this->manager->transaction(function () use ($connection, $table, $queueName, $timestamp, $workerId): ?QueuedJob {
            $row = DB::table($table, $connection)
                ->where('queue', '=', $queueName)
                ->where('available_at', '<=', $timestamp)
                ->whereNull('reserved_at')
                ->whereNull('completed_at')
                ->whereNull('failed_at')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($row === null) {
                return null;
            }

            $attempts = (int) ($row['attempts'] ?? 0) + 1;
            $updated = DB::table($table, $connection)
                ->where('id', '=', $row['id'])
                ->update([
                    'attempts' => $attempts,
                    'reserved_at' => $timestamp,
                    'reserved_by' => $workerId,
                    'updated_at' => $timestamp,
                ]);

            if ($updated !== 1) {
                return null;
            }

            $job = QueuedJob::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'queue' => $row['queue'],
                'payload' => json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR),
                'attempts' => $attempts,
                'availableAt' => (int) $row['available_at'],
                'createdAt' => (int) $row['created_at'],
            ]);

            $this->logger->debug('Job popped', [
                'id' => $job->id(),
                'name' => $job->name(),
            ]);

            return $job;
        }, $connection);
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $config = $this->configuration();
        $timestamp = time();
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        DB::table($table, $connection)
            ->where('id', '=', $job->id())
            ->update([
                'available_at' => $timestamp + max(0, $delaySeconds),
                'reserved_at' => null,
                'reserved_by' => null,
                'updated_at' => $timestamp,
            ]);

        $this->logger->info('Job released', [
            'id' => $job->id(),
            'delay' => $delaySeconds,
        ]);

        return $job->withAvailableAt($timestamp + max(0, $delaySeconds));
    }

    public function complete(QueuedJob $job): void
    {
        $config = $this->configuration();
        $timestamp = time();
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        DB::table($table, $connection)
            ->where('id', '=', $job->id())
            ->update([
                'completed_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        $this->logger->info('Job completed', ['id' => $job->id()]);
    }

    public function fail(QueuedJob $job, ?string $error = null): void
    {
        $config = $this->configuration();
        $timestamp = time();
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        DB::table($table, $connection)
            ->where('id', '=', $job->id())
            ->update([
                'failed_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        $this->logger->error('Job failed', [
            'id' => $job->id(),
            'error' => $error,
        ]);
    }

    /**
     * @return list<QueuedJob>
     */
    public function pending(?string $queue = null): array
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];
        $timestamp = time();
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        $rows = DB::table($table, $connection)
            ->where('queue', '=', $queueName)
            ->where('available_at', '<=', $timestamp)
            ->whereNull('reserved_at')
            ->whereNull('completed_at')
            ->whereNull('failed_at')
            ->orderBy('created_at', 'asc')
            ->get();

        $jobs = [];
        foreach ($rows as $row) {
            $jobs[] = QueuedJob::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'queue' => $row['queue'],
                'payload' => json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR),
                'attempts' => (int) $row['attempts'],
                'availableAt' => (int) $row['available_at'],
                'createdAt' => (int) $row['created_at'],
            ]);
        }

        return $jobs;
    }

    /**
     * @return list<QueuedJob>
     */
    public function failed(?string $queue = null): array
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        $rows = DB::table($table, $connection)
            ->where('queue', '=', $queueName)
            ->whereNotNull('failed_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $jobs = [];
        foreach ($rows as $row) {
            $jobs[] = QueuedJob::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'queue' => $row['queue'],
                'payload' => json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR),
                'attempts' => (int) $row['attempts'],
                'availableAt' => (int) $row['available_at'],
                'createdAt' => (int) $row['created_at'],
            ]);
        }

        return $jobs;
    }

    public function flush(?string $queue = null): int
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        return DB::table($table, $connection)
            ->where('queue', '=', $queueName)
            ->whereNotNull('completed_at')
            ->delete();
    }

    public function size(?string $queue = null): int
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];
        $timestamp = time();
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        return DB::table($table, $connection)
            ->where('queue', '=', $queueName)
            ->where('available_at', '<=', $timestamp)
            ->whereNull('reserved_at')
            ->whereNull('completed_at')
            ->whereNull('failed_at')
            ->count();
    }

    public function flushFailed(?string $queue = null): int
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        return DB::table($table, $connection)
            ->where('queue', '=', $queueName)
            ->whereNotNull('failed_at')
            ->delete();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int,
     *     tries: int|null
     * }
     */
    private function configuration(): array
    {
        if ($this->queueConfig !== null) {
            return $this->queueConfig;
        }

        $this->config->loadIfExists(QueueConfig::KEY . '.php');
        $this->queueConfig = QueueConfig::merge($this->app, $this->config->getArray(QueueConfig::KEY, []));

        return $this->queueConfig;
    }
}
