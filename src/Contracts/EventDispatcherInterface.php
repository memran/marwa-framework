<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(object $event): object;

    /**
     * @param callable|array<int|string, mixed>|string $listener
     */
    public function listen(string $event, callable|array|string $listener, int $priority = 0): void;
}
