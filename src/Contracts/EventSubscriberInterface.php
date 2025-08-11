<?php

declare(strict_types=1);

namespace Marwa\App\Contracts;

use Marwa\App\Events\EventManager;

/**
 * Contract for class-based subscribers (Laravel-style).
 */
interface EventSubscriberInterface
{
    /**
     * Register the listeners for the subscriber.
     *
     * Example:
     * return [
     *   UserRegistered::class => 'handleUserRegistered',
     *   'order.paid'          => ['handlePaid', 10],
     * ];
     *
     * Value can be:
     * - string method name
     * - array [method, priority]
     *
     * @return array<string, string|array{0:string,1:int}>
     */
    public static function subscribeEvents(): array;

    /**
     * Optional: called after registration for custom wiring.
     */
    public function subscribe(EventManager $events): void;
}
