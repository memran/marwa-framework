<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

class NamedEvent extends AbstractEvent
{
    /**
     * @param string $name
     * @param array|object|null $payload
     */
    public function __construct(string $name, public array|object|null $payload = null)
    {
        parent::__construct($name);
    }
}
