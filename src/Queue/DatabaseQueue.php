<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use DateTimeImmutable;
use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Supports\Config;
use Memran\MarwaDb\Database;
use Memran\MarwaDb\Schema\Blueprint;
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
     *     retryAfter: int
     * }|null
     */
    private ?array $queueConfig = null;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
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

        $db = $this->database();
        $db->table($config['database']['table'])->insert([
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

    /**
     * @param array<string, mixed> $payload
     */
    public function pushAt(string $name, int $timestamp, array $payload = [], ?string $queue = null): QueuedJob
    {
        return $this->push($name, $payload, $queue, $timestamp - time());
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{expression: string, timezone?: string} $schedule
     */
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

        $db = $this->database();

        $row = $db->table($config['database']['table'])
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

        $updated = $db->table($config['database']['table'])
            ->where('id', '=', $row['id'])
            ->update([
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
            'attempts' => $row['attempts'],
            'available_at' => $row['available_at'],
            'reserved_at' => $timestamp,
            'reserved_by' => $workerId,
            'completed_at' => null,
            'failed_at' => null,
            'created_at' => $row['created_at'],
        ]);

        $this->logger->debug('Job popped', [
            'id' => $job->id(),
            'name' => $job->name(),
        ]);

        return $job;
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $config = $this->configuration();
        $timestamp = time();

        $this->database()->table($config['database']['table'])
            ->where('id', '=', $job->id())
            ->update([
                'attempts' => $job->attempts() + 1,
                'available_at' => $timestamp + max(0, $delaySeconds),
                'reserved_at' => null,
                'reserved_by' => null,
                'updated_at' => $timestamp,
            ]);

        $this->logger->info('Job released', [
            'id' => $job->id(),
            'delay' => $delaySeconds,
        ]);

        return $job;
    }

    public function complete(QueuedJob $job): void
    {
        $config = $this->configuration();
        $timestamp = time();

        $this->database()->table($config['database']['table'])
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

        $this->database()->table($config['database']['table'])
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
     * @return array<string, mixed>
     */
    public function pending(?string $queue = null): array
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];
        $timestamp = time();

        $rows = $this->database()->table($config['database']['table'])
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
                'attempts' => $row['attempts'],
                'available_at' => $row['available_at'],
                'created_at' => $row['created_at'],
            ]);
        }

        return $jobs;
    }

    /**
     * @return array<string, mixed>
     */
    public function failed(?string $queue = null): array
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];

        $rows = $this->database()->table($config['database']['table'])
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
                'attempts' => $row['attempts'],
                'available_at' => $row['available_at'],
                'failed_at' => $row['failed_at'],
                'created_at' => $row['created_at'],
            ]);
        }

        return $jobs;
    }

    public function flush(?string $queue = null): int
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];

        return $this->database()->table($config['database']['table'])
            ->where('queue', '=', $queueName)
            ->whereNotNull('completed_at')
            ->delete();
    }

    public function flushFailed(?string $queue = null): int
    {
        $config = $this->configuration();
        $queueName = $queue ?? $config['default'];

        return $this->database()->table($config['database']['table'])
            ->where('queue', '=', $queueName)
            ->whereNotNull('failed_at')
            ->delete();
    }

    public static function migrate(Database $db, string $table): void
    {
        $schema = $db->schema();

        if (!$schema->hasTable($table)) {
            $schema->create($table, function (Blueprint $table) {
                $table->id('id', 32);
                $table->string('name', 255);
                $table->string('queue', 64);
                $table->text('payload');
                $table->integer('attempts')->default(0);
                $table->integer('available_at');
                $table->integer('reserved_at')->nullable();
                $table->string('reserved_by', 64)->nullable();
                $table->integer('completed_at')->nullable();
                $table->integer('failed_at')->nullable();
                $table->integer('created_at');
                $table->integer('updated_at');

                $table->index(['queue', 'available_at', 'reserved_at', 'completed_at', 'failed_at'], 'queue_status');
                $table->index(['reserved_at', 'reserved_by'], 'reserved');
            });
        }
    }

    private function database(): Database
    {
        $config = $this->configuration();
        $connection = $config['database']['connection'];

        return Database::connection($connection);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int
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