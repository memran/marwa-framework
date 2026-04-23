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
    public function pushAt(string $name, array $payload = [], ?string $queue = null, int $timestamp): QueuedJob;

    /**
     * Push a recurring job
     * @param array<string, mixed> $payload
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function pushRecurring(string $name, array $payload = [], ?string $queue = null, array $schedule): QueuedJob;

    /**
     * Pop the next available job from the queue
     */
    public function pop(?string $queue = null, ?\DateTimeImmutable $now = null): ?QueuedJob;
}
