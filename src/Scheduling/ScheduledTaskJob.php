<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling;

use Marwa\Framework\Application;
use Marwa\Framework\Queue\NonRetryableJobException;

final class ScheduledTaskJob
{
    /** @param array<string,mixed> $payload */
    public function __construct(private array $payload) {}

    public function handle(Application $app): void
    {
        $name = $this->payload['task'] ?? null;
        $token = $this->payload['token'] ?? null;
        if (!is_string($name) || $name === '' || !is_string($token) || strlen($token) !== 32) {
            throw new NonRetryableJobException('Invalid scheduled task reservation.');
        }
        $app->schedule()->runReserved($name, $token);
    }
}
