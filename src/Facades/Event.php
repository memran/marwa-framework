<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;

/**
 * @method static object dispatch(object $event)
 * @method static void listen(string $event, callable|array<int|string, mixed>|string $listener, int $priority = 0)
 */
final class Event extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EventDispatcherAdapter::class;
    }
}
