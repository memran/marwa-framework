<?php

declare(strict_types=1);

namespace Marwa\App\Events;

use League\Event\EventDispatcher;
use League\Event\PrioritizedListenerRegistry;

/**
 * Laravel-flavored facade over league/event's EventDispatcher.
 */
final class EventManager
{
    private EventDispatcher $dispatcher;

    public function __construct()
    {
        //$listenerRegistry = new PrioritizedListenerRegistry();
        //$this->dispatcher = new EventDispatcher($listenerRegistry);
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Register a listener for an event.
     *
     * @param string $event  Class name (preferred) or string name
     * @param callable|string $listener Callable or class-string with handle($event)
     * @param int $priority Higher runs earlier (0 default)
     */
    public function listen(string $event, callable $listener, int $priority = 0): void
    {
        //$callable = $this->normalizeListener($listener);
        // league/event v3 offers subscribeTo($event, $listener)
        //dd($event, $listener);
        $this->dispatcher->subscribeTo($event, $listener, $priority);
    }

    /**
     * Dispatch an event object (preferred) or a string with payload.
     *
     * @param object|string $event
     * @param mixed ...$payload
     */
    public function dispatch(object $event): object
    {
        // If string event name, wrap into a generic object with public payload
        // if (is_string($event)) {
        //     $event = (object) ['name' => $event, 'payload' => $payload];
        // }

        return $this->dispatcher->dispatch($event);
    }

    public function register(array $config = []): void
    {

        foreach (($config ?? []) as $event => $listeners) {
            foreach ($listeners as $listener) {
                if (is_array($listener)) {
                    [$classOrCallable, $priority] = [$listener[0], (int)($listener[1] ?? 0)];
                    $this->listen($event, new $classOrCallable(), $priority);
                } else {
                    $this->listen($event, new $listener(), 0);
                }
            }
        }
    }
}
