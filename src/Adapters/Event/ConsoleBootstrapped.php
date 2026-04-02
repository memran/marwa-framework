<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class ConsoleBootstrapped extends AbstractEvent
{
    public readonly DateTimeImmutable $time;

    public function __construct(
        public readonly string $consoleName = 'Marwa Console',
        public readonly string $version = 'dev',
        public readonly int $commandCount = 0
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}
