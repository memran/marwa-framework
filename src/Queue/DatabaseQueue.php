<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use DateTimeImmutable;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Facades\DB;
use Marwa\DB\Query\Builder;
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
        return $this->push($name, $payload, $queue, max(0, $timestamp - time()));
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
        $lease = max(1, (int) $this->config->get('queue.reservationSeconds', 300));
        $maxAttempts = max(1, $this->maxAttempts ?? $config['tries'] ?? (int) ($config['retryAfter'] / 30));
        // Compare-and-set in autocommit avoids a stale read snapshot and holds no lock during execution.
        for ($collision = 0; $collision < 20; $collision++) {
            $eligible = $this->eligible($queueName, $timestamp, $lease);
            $row = (clone $eligible)->select('id', 'attempts')->orderBy('created_at')->orderBy('id')->first();
            if ($row === null) {
                return null;
            }
            $claim = $this->eligible($queueName, $timestamp, $lease)
                ->where('id', '=', $row['id'])->where('attempts', '=', $row['attempts']);
            if ((int) $row['attempts'] >= $maxAttempts) {
                $claim->update(['failed_at' => $timestamp, 'reserved_at' => null, 'reserved_by' => null, 'updated_at' => $timestamp]);
                continue;
            }
            $token = bin2hex(random_bytes(16));
            $attempts = (int) $row['attempts'] + 1;
            if ($claim->update(['attempts' => $attempts, 'reserved_at' => $timestamp,
                'reserved_by' => $token, 'updated_at' => $timestamp]) !== 1) {
                continue;
            }
            $row = $this->ownedById((string) $row['id'], $token)->first();
            if ($row === null) {
                continue;
            }
            try {
                $payload = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($payload)) {
                    throw new \UnexpectedValueException('Job payload must be an object or array.');
                }
            } catch (\Throwable $error) {
                $this->ownedById((string) $row['id'], $token)->update([
                    'failed_at' => $timestamp, 'reserved_at' => null, 'reserved_by' => null, 'updated_at' => $timestamp,
                ]);
                $this->logger->error('Invalid queued payload', ['id' => $row['id'], 'error' => $error->getMessage()]);
                continue;
            }
            return QueuedJob::fromArray([
                'id' => $row['id'], 'name' => $row['name'], 'queue' => $row['queue'],
                'payload' => $payload, 'attempts' => $attempts, 'reservationToken' => $token,
                'availableAt' => (int) $row['available_at'], 'createdAt' => (int) $row['created_at'],
            ]);
        }
        return null;
    }

    private ?int $maxAttempts = null;

    public function setMaxAttempts(int $attempts): void
    {
        $this->maxAttempts = max(1, $attempts);
    }

    private function eligible(string $queue, int $timestamp, int $lease): Builder
    {
        $config = $this->configuration();
        return (new Builder($this->manager, $config['database']['connection']))->table($config['database']['table'])
            ->where('queue', '=', $queue)->where('available_at', '<=', $timestamp)
            ->whereNull('completed_at')->whereNull('failed_at')
            ->where(function (Builder $query) use ($timestamp, $lease): void {
                $query->whereNull('reserved_at')->orWhere('reserved_at', '<=', $timestamp - $lease);
            });
    }

    private function ownedById(string $id, ?string $token): Builder
    {
        $config = $this->configuration();
        $query = (new Builder($this->manager, $config['database']['connection']))->table($config['database']['table'])
            ->where('id', '=', $id)->whereNull('completed_at')->whereNull('failed_at');
        return $token === null ? $query->whereNull('reserved_by')->whereNull('reserved_at')
            : $query->where('reserved_by', '=', $token);
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $config = $this->configuration();
        $timestamp = time();
        $connection = $config['database']['connection'];
        $table = $config['database']['table'];

        $this->ownedById($job->id(), $job->reservationToken())
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

        $this->ownedById($job->id(), $job->reservationToken())
            ->update([
                'completed_at' => $timestamp,
                'reserved_at' => null,
                'reserved_by' => null,
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

        $this->ownedById($job->id(), $job->reservationToken())
            ->update([
                'failed_at' => $timestamp,
                'reserved_at' => null,
                'reserved_by' => null,
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
