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
    /**
     * @var string
     */
    protected string $name;

    /**
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        // default: use FQCN as event name
        $this->name = $name ?? static::class;
    }

    /**
     * Get the event name (used by dispatcher).
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
