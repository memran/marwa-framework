<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

/**
 * Base abstract event with a name.
 *
 * If no name is given, the event class name will be used.
 */
abstract class AbstractEvent
{
    public readonly string $name;

    public function __construct(?string $name = null)
    {
        $this->name = $name ?? static::class;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
