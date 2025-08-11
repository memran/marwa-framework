<?php

declare(strict_types=1);

namespace Marwa\App\Events;

use Marwa\App\Events\EventManager;

/**
 * Reads a simple array config and auto-registers listeners & subscribers.
 *
 * Expected config:
 * [
 *   'listeners' => [
 *       UserRegistered::class => [
 *          App\Listeners\SendWelcomeEmail::class,
 *          'App\\Listeners\\Foo@method',
 *          [App\Listeners\HighPriority::class, 10],
 *       ],
 *       'order.paid' => [
 *          [App\Listeners\PaidHandler::class, 5]
 *       ]
 *   ],
 *   'subscribers' => [
 *       App\Subscribers\AuditSubscriber::class
 *   ],
 * ]
 */
final class EventAutoRegistrar
{
    /**
     * @param array{
     *   listeners?: array<string, array<int, string|array{0:string,1?:int}>>,
     *   subscribers?: array<int, string>
     * } $config
     */
    public function __construct(
        private readonly EventManager $events,
        private readonly array $config = []
    ) {}

    public function register(): void
    {

        foreach (($this->config ?? []) as $event => $listeners) {
            foreach ($listeners as $listener) {
                if (is_array($listener)) {
                    [$classOrCallable, $priority] = [$listener[0], (int)($listener[1] ?? 0)];
                    $this->events->listen($event, $classOrCallable, $priority);
                } else {
                    $this->events->listen($event, $listener, 0);
                }
            }
        }
    }
}
