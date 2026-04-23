<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class RedisQueue implements QueueInterface
{
    private const PENDING_LIST = 'queue:%s:pending';
    private const PROCESSING_SET = 'queue:%s:processing';
    private const JOB_KEY = 'job:%s';
    private const ID_KEY = 'queue:%s:next_id';

    /**
     * @var array{enabled:bool,default:string,path:string,retryAfter:int}|null
     */
    private ?array $queueConfig = null;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        $config = $this->configuration();

        if (!$config['enabled']) {
            throw new \RuntimeException('Queue support is disabled.');
        }

        $queueName = $queue !== null && $queue !== '' ? $queue : $config['default'];
        $timestamp = time();

        $id = $this->cache->increment(sprintf(self::ID_KEY, $queueName), 1, 1, 0);
        if ($id === false) {
            throw new \RuntimeException('Failed to generate job ID.');
        }

        $job = new QueuedJob(
            id: (string) $id,
            name: $name,
            queue: $queueName,
            payload: $payload,
            attempts: 0,
            availableAt: $timestamp + max(0, $delaySeconds),
            createdAt: $timestamp
        );

        $this->cache->put(sprintf(self::JOB_KEY, $job->id()), $job->toArray(), null);
        $this->cache->put(sprintf(self::PENDING_LIST, $queueName), $this->addToList($queueName, $job->id(), $job->availableAt()), null);

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
        $payload['_recurring'] = $schedule;

        return $this->push($name, $payload, $queue);
    }

    public function pop(?string $queue = null, ?\DateTimeImmutable $now = null): ?QueuedJob
    {
        $config = $this->configuration();

        if (!$config['enabled']) {
            return null;
        }

        $queueName = $queue !== null && $queue !== '' ? $queue : $config['default'];
        $timestamp = ($now ?? new \DateTimeImmutable())->getTimestamp();

        $pendingKey = sprintf(self::PENDING_LIST, $queueName);
        $list = $this->cache->get($pendingKey, []);

        if (!is_array($list)) {
            return null;
        }

        foreach ($list as $id => $availableAt) {
            if ($availableAt > $timestamp) {
                continue;
            }

            $jobKey = sprintf(self::JOB_KEY, $id);
            $data = $this->cache->get($jobKey);

            if (!is_array($data)) {
                $this->removeFromList($queueName, $id);
                continue;
            }

            $job = QueuedJob::fromArray($data);
            $reserved = $job->withAttempts($job->attempts() + 1);

            // Move to processing
            $this->cache->put($jobKey, $reserved->toArray(), null);
            $this->cache->put(sprintf(self::PROCESSING_SET, $queueName), $this->addToSet($queueName, $reserved->id()), null);
            $this->removeFromList($queueName, $id);

            $this->logger->debug('Job popped', [
                'id' => $reserved->id(),
                'name' => $reserved->name(),
            ]);

            return $reserved;
        }

        return null;
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $timestamp = time();
        $released = $job->withAvailableAt($timestamp + max(0, $delaySeconds));

        $jobKey = sprintf(self::JOB_KEY, $job->id());
        $this->cache->put($jobKey, $released->toArray(), null);

        $this->removeFromSet($job->queue(), $job->id());
        $this->cache->put(sprintf(self::PENDING_LIST, $job->queue()), $this->addToList($job->queue(), $released->id(), $released->availableAt()), null);

        $this->logger->info('Job released', [
            'id' => $job->id(),
            'delay' => $delaySeconds,
        ]);

        return $released;
    }

    public function size(?string $queue = null): int
    {
        $queueName = $queue ?? $this->configuration()['default'];
        $list = $this->cache->get(sprintf(self::PENDING_LIST, $queueName), []);

        return is_array($list) ? count($list) : 0;
    }

    public function complete(QueuedJob $job): void
    {
        $jobKey = sprintf(self::JOB_KEY, $job->id());
        $processingKey = sprintf(self::PROCESSING_SET, $job->queue());

        $this->cache->forget($jobKey);
        $this->removeFromSet($job->queue(), $job->id());

        $this->logger->info('Job completed', ['id' => $job->id()]);
    }

    public function fail(QueuedJob $job, ?string $error = null): void
    {
        $jobKey = sprintf(self::JOB_KEY, $job->id());
        $processingKey = sprintf(self::PROCESSING_SET, $job->queue());

        $this->removeFromSet($job->queue(), $job->id());

        $failedKey = sprintf('queue:%s:failed', $job->queue());
        $failed = $this->cache->get($failedKey, []);
        if (!is_array($failed)) {
            $failed = [];
        }
        $failed[$job->id()] = [
            'job' => $job->toArray(),
            'error' => $error,
            'failed_at' => time(),
        ];
        $this->cache->put($failedKey, $failed, null);

        $this->cache->forget($jobKey);

        $this->logger->error('Job failed', [
            'id' => $job->id(),
            'error' => $error,
        ]);
    }

    /**
     * @return array{enabled:bool,default:string,path:string,retryAfter:int}
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

    /**
     * @param array<string, int> $list
     * @return array<string, int>
     */
    private function addToList(string $queue, string $id, int $availableAt, array $list = []): array
    {
        $list[$id] = $availableAt;
        asort($list, SORT_NUMERIC);

        return $list;
    }

    private function removeFromList(string $queue, string $id): void
    {
        $pendingKey = sprintf(self::PENDING_LIST, $queue);
        $list = $this->cache->get($pendingKey, []);

        if (is_array($list) && isset($list[$id])) {
            unset($list[$id]);
            $this->cache->put($pendingKey, $list, null);
        }
    }

    /**
     * @param array<string, true> $set
     * @return array<string, true>
     */
    private function addToSet(string $queue, string $id, array $set = []): array
    {
        $set[$id] = true;

        return $set;
    }

    private function removeFromSet(string $queue, string $id): void
    {
        $processingKey = sprintf(self::PROCESSING_SET, $queue);
        $set = $this->cache->get($processingKey, []);

        if (is_array($set) && isset($set[$id])) {
            unset($set[$id]);
            $this->cache->put($processingKey, $set, null);
        }
    }
}
