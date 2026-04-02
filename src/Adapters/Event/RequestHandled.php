<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use DateTimeImmutable;

final class RequestHandled extends AbstractEvent
{
    public readonly DateTimeImmutable $time;

    public function __construct(
        public readonly string $method = 'GET',
        public readonly string $path = '/',
        public readonly int $statusCode = 200
    ) {
        parent::__construct();
        $this->time = new DateTimeImmutable();
    }
}
