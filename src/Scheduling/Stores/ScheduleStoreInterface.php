<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\Framework\Scheduling\Task;

interface ScheduleStoreInterface
{
    public function acquireLock(Task $task, \DateTimeImmutable $time, int $ttlSeconds): mixed;

    public function releaseLock(Task $task, mixed $lock): void;

    public function record(Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void;
}
