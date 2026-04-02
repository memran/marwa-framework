<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class ErrorHandlerBootstrapped extends AbstractEvent
{
    public readonly DateTimeImmutable $time;

    public function __construct(
        public readonly bool $enabled = false
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}
