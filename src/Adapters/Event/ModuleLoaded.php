<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class ModuleLoaded extends AbstractEvent
{
    public function __construct(
        public readonly string $slug,
        string $name,
        public readonly DateTimeImmutable $time = new DateTimeImmutable()
    ) {
        parent::__construct($name);
    }
}