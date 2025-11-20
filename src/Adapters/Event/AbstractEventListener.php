<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use Marwa\Framework\Adapters\Event\AbstractEvent;

/**
 * Base listener for events.
 *
 * Extend this and implement handle().
 * Works with Symfony dispatcher because it's invokable.
 */
abstract class AbstractEventListener
{
    /**
     * Handle the event.
     *
     * @param AbstractEvent $event
     * @return void
     */
    abstract public function handle(AbstractEvent $event): void;

    /**
     * Make listener invokable so dispatcher can call it directly.
     *
     * @param AbstractEvent $event
     * @return void
     */
    public function __invoke(AbstractEvent $event): void
    {
        $this->handle($event);
    }
}
