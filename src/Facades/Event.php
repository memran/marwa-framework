<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;

/**
 * @method static void dispatch(object|string $event)
 */
final class Event extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EventDispatcherAdapter::class;
    }
}
