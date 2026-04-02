<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class ProvidersBootstrapped extends AbstractEvent
{
    public readonly DateTimeImmutable $time;

    /**
     * @param list<class-string> $providers
     */
    public function __construct(
        public readonly array $providers = []
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}
