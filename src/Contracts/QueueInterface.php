<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Marwa\Framework\Queue\QueuedJob;

interface QueueInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob;

    /**
     * Push a job to be executed at a specific timestamp
     * @param array<string, mixed> $payload
     */
    public function pushAt(string $name, int $timestamp, array $payload = [], ?string $queue = null): QueuedJob;

    /**
     * Push a recurring job
     * @param array<string, mixed> $payload
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function pushRecurring(string $name, array $schedule, array $payload = [], ?string $queue = null): QueuedJob;

    /**
     * Pop the next available job from the queue
     */
    public function pop(?string $queue = null, ?\DateTimeImmutable $now = null): ?QueuedJob;

    /**
     * Release a failed job back to the queue with optional delay
     */
    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob;

    /**
     * Mark a reserved job as completed.
     */
    public function complete(QueuedJob $job): void;

    /**
     * Mark a reserved job as permanently failed.
     */
    public function fail(QueuedJob $job, ?string $error = null): void;

    /**
     * Count pending jobs.
     */
    public function size(?string $queue = null): int;
}
