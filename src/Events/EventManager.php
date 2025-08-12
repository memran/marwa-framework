<?php

declare(strict_types=1);

namespace Marwa\App\Events;


use League\Event\EventDispatcher;
use League\Event\PrioritizedListenerRegistry;
use Marwa\App\Exceptions\InvalidArgumentException;
use League\Event\ListenerPriority;
use League\Event\HasEventName;

/**
 * Laravel-flavored facade over league/event's EventDispatcher.
 */
final class EventManager
{
    private EventDispatcher $dispatcher;

    public function __construct()
    {
        $listenerRegistry = new PrioritizedListenerRegistry();
        $this->dispatcher = new EventDispatcher($listenerRegistry);
    }

    /**
     * Register a listener for an event.
     *
     * @param string $event  Class name (preferred) or string name
     * @param callable|string $listener Callable or class-string with handle($event)
     * @param int $priority Higher runs earlier (0 default)
     */
    public function listen(string $event, callable|string|array $listener, int|string $priority = 0): void
    {
        $callable = $this->resolve($listener);
        // league/event v3 offers subscribeTo($event, $listener)
        //dd($event, $listener);
        $this->dispatcher->subscribeTo($event, $callable, $this->convertStringToPriority($priority));
    }

    /**
     * Register a once-only listener.
     */
    public function listenOnce(string $event, callable|string|array $listener, int|string $priority = 0): void
    {
        $callable = $this->resolve($listener);
        $prio     = $this->convertStringToPriority($priority);

        $this->dispatcher->subscribeOnceTo($event, $callable, $prio);
    }
    /**
     * Dispatch an event object (preferred) or a string with payload.
     *
     * @param object|string $event
     * @param mixed ...$payload
     */
    public function dispatch(object|string $event): object
    {
        if (is_string($event)) {
            $event = new class($event) implements HasEventName {
                public function __construct(private string $name) {}
                public function eventName(): string
                {
                    return $this->name;
                }
            };
        }
        return $this->dispatcher->dispatch($event);
    }

    public function register(array $config = []): void
    {

        foreach (($config ?? []) as $event => $listeners) {
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
    /**
     * @param callable|string|array{string|object, string} $handler
     * @return callable
     *
     * @throws InvalidArgumentException if handler cannot be resolved
     */
    public static function resolve(callable|string|array $handler): callable
    {
        // Already callable
        if (is_callable($handler)) {
            return $handler;
        }

        // Array form: [class/object, method]
        if (is_array($handler) && count($handler) === 2) {
            if (is_callable($handler)) {
                return $handler;
            }
            throw new InvalidArgumentException('Array handler is not callable.');
        }

        // String form: "Class@method" or "Class::method"
        if (is_string($handler)) {
            $methodSeparator = str_contains($handler, '@') ? '@' : (str_contains($handler, '::') ? '::' : null);
            $class = $handler;
            $method = '__invoke'; // default method if not specified

            if ($methodSeparator) {
                [$class, $method] = explode($methodSeparator, $handler, 2);
            }

            if (!class_exists($class)) {
                throw new InvalidArgumentException("Class '{$class}' does not exist.");
            }

            // Get instance from container if possible, otherwise instantiate
            $instance = app()->has($class) ? app()->get($class) : new $class();

            if (!method_exists($instance, $method)) {
                throw new InvalidArgumentException("Method '{$method}' does not exist on class '{$class}'.");
            }

            return [$instance, $method];
        }

        throw new InvalidArgumentException('Handler is not a valid callable or resolvable string.');
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
