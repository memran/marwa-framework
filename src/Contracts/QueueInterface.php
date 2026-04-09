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
}
