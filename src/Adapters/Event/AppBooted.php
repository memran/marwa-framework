<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;
use Marwa\Framework\Adapters\Event\AbstractEvent;

/**
 * Fired when the Application has finished booting all service providers.
 * Useful for listeners that perform post-boot initialization
 * (e.g., warm caches, register dynamic routes, load modules).
 */
final class AppBooted extends AbstractEvent
{
    /** Timestamp when the event was fired. */
    public readonly DateTimeImmutable $time;

    public function __construct(
        public readonly string $environment = 'production',
        public readonly string $basePath = ''
    ) {
        $this->time = new DateTimeImmutable();
    }
}
