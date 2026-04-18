<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class ModuleLoaded extends AbstractEvent
{
    public readonly DateTimeImmutable $time;

    public function __construct(
        public readonly string $slug,
        public readonly string $name
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}