<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(object $event): mixed;
    public function listen(string $event, callable $listener): void;
}
