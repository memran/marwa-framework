<?php

declare(strict_types=1);

namespace Marwa\App\Events;

use Marwa\Event\Resolver\ListenerResolver;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Bus\EventBus;
use Psr\Container\ContainerInterface;
use Marwa\App\Events\ListenerPriority;
use Marwa\App\Exceptions\InvalidArgumentException;

/**
 * Laravel-flavored facade over league/event's EventDispatcher.
 */
final class EventManager
{
    private EventBus $events;


    public function __construct(?ContainerInterface $container = null)
    {
        // Initialize event system
        $resolver   = new ListenerResolver($container);
        $provider   = new ListenerProvider($resolver);
        $dispatcher = new EventDispatcher($provider, false);

        $this->events = new EventBus($provider, $dispatcher);
    }

    /**
     * Register a listener for an event.
     *
     * @param string $event  Class name (preferred) or string name
     * @param callable|string $listener Callable or class-string with handle($event)
     * @param int $priority Higher runs earlier (0 default)
     */
    public function listen(string $event, callable|string $listener, int|string $priority = 0): void
    {

        $this->events->listen($event, $listener, $this->convertStringToPriority($priority));
    }

    /**
     * Dispatch an event object (preferred) or a string with payload.
     *
     * @param object|string $event
     * @param mixed ...$payload
     */
    public function dispatch(object|string $event): object
    {
        return $this->events->dispatch($event);
    }

    /**
     * 
     */
    public function register(array $config = []): void
    {

        foreach (($config ?? []) as $event => $listeners) {
            //dd($event, $listeners);
            if (is_string($listeners)) {
                $this->listen($event, $listeners, 0);
            } else if (is_array($listeners)) {
                foreach ($listeners as $listener) {
                    if (is_array($listener)) {
                        [$classOrCallable, $priority] = [$listener[0], $listener[1]];

                        $this->listen($event, $classOrCallable, $this->convertStringToPriority($priority));
                    } else {
                        $this->listen($event, $listener, 0);
                    }
                }
            }
        }
    }

    /**
     * Map string priorities to ListenerPriority constants.
     */
    protected function convertStringToPriority(string|int $priority): int
    {
        if (is_int($priority)) {
            return $priority;
        }

        return match (strtolower(trim($priority))) {
            'high'   => ListenerPriority::HIGH,
            'normal' => ListenerPriority::NORMAL,
            'low'    => ListenerPriority::LOW,
            default  => ListenerPriority::NORMAL, // safe default
        };
    }
}
