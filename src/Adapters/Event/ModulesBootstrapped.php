<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class ModulesBootstrapped extends AbstractEvent
{
    public readonly DateTimeImmutable $time;

    /**
     * @param list<string> $modules
     */
    public function __construct(
        public readonly array $modules = []
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}
