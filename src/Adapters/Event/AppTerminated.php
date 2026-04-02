<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

/**
 * Fired when the Application is terminating.
 * Useful for cleanup tasks, logging, or resource deallocation.
 */
final class AppTerminated extends AbstractEvent
{
    /** Timestamp when the event was fired. */
    public readonly DateTimeImmutable $time;

    public function __construct(
        public readonly int $statusCode = 0
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}
