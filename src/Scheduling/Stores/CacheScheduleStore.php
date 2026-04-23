<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Scheduling\Task;

final class CacheScheduleStore implements ScheduleStoreInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $namespace = 'schedule'
    ) {}

    public function acquireLock(Task $task, \DateTimeImmutable $time, int $ttlSeconds): mixed
    {
        $key = $this->lockKey($task);
        $value = [
            'task' => $task->name(),
            'expires_at' => $time->modify(sprintf('+%d seconds', $ttlSeconds))->format(DATE_ATOM),
        ];

        // Retry loop to reduce race condition window
        for ($i = 0; $i < 3; $i++) {
            if ($this->cache->has($key)) {
                return null;
            }

            if ($this->cache->put($key, $value, max(1, $ttlSeconds))) {
                return $key;
            }

            usleep(10000); // 10ms delay before retry
        }

        return null;
    }

    public function releaseLock(Task $task, mixed $lock): void
    {
        if (!is_string($lock) || $lock === '') {
            return;
        }

        $this->cache->forget($lock);
    }

    public function record(Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void
    {
        $key = $this->stateKey($task);
        $state = $this->cache->get($key, []);

        if (!is_array($state)) {
            $state = [];
        }

        $state['name'] = $task->name();
        $state['description'] = $task->description();
        $state['status'] = $status;
        $state['last_message'] = $message;
        $state['updated_at'] = $time->format(DATE_ATOM);

        if ($status === 'success') {
            $state['last_ran_at'] = $time->format(DATE_ATOM);
            $state['last_finished_at'] = $time->format(DATE_ATOM);
        }

        if ($status === 'failed') {
            $state['last_failed_at'] = $time->format(DATE_ATOM);
        }

        if ($status === 'skipped') {
            $state['last_skipped_at'] = $time->format(DATE_ATOM);
        }

        $this->cache->forever($key, $state);
    }

    private function lockKey(Task $task): string
    {
        return $this->normalizeSegment($this->namespace) . '.locks.' . $this->normalizeName($task->name());
    }

    private function stateKey(Task $task): string
    {
        return $this->normalizeSegment($this->namespace) . '.state.' . $this->normalizeName($task->name());
    }

    private function normalizeName(string $name): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_\-]+/', '-', strtolower($name)) ?: 'task';

        return trim($normalized, '-');
    }

    private function normalizeSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_\-\.]+/', '-', strtolower(trim($value))) ?: 'schedule';

        return trim($normalized, '-.');
    }
}
