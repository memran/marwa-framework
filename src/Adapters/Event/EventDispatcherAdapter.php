<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use Psr\Container\ContainerInterface;
use Marwa\Event\Resolver\ListenerResolver;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\Subscriber;
//use Marwa\Framework\Supports\Config;
use Marwa\Framework\Facades\Config;

/**
 * Class EventDispatcherAdapter
 *
 * A PSR-14 compatible adapter for event dispatching using Marwa Event library.
 *
 * - dispatch(object $event): object  (PSR-14)
 * - listen(string $event, callable|string|array $listener)
 * - subscribe(string|EventSubscriberInterface $subscriber)
 * - loadFromArray(array $config)
 * - fire(object $event) alias of dispatch()
 */
class EventDispatcherAdapter
{
    protected ContainerInterface $container;

    /**
     * @var EventDispatcher
     */
    protected EventDispatcher $dispatcher;

    /**
     * Optional listener resolver
     * @var ListenerResolver
     */
    protected ListenerResolver $resolver;

    /**
     * @var ListenerProvider
     */
    protected ListenerProvider $provider;

    /**
     * @var EventBus
     */
    protected EventBus $bus;

    /**
     * EventDispatcherAdapter constructor.
     *
     * @param ContainerInteface callable|null $resolver A callable($class):object to resolve listener/subscriber classes.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->init();
    }

    /**
     * Initialize the dispatcher, provider, and resolver.
     * @return void
     */
    private function init(): void
    {
        $this->resolver   = new ListenerResolver($this->container);           // optionally pass a PSR-11 container
        $this->provider   = new ListenerProvider($this->resolver);
        $this->dispatcher = new EventDispatcher($this->provider);
        $this->bus = new EventBus($this->provider, $this->dispatcher);
        $this->loadFromArray();
    }
    /**
     * PSR-14 dispatch method.
     *
     * @param object $event
     * @return void
     */
    public function dispatch(object|string $event): void
    {
        $this->bus->dispatch($event);
    }

    /**
     *  Alias of dispatch().
     *
     * @param object $event
     * @return object
     */
    public function fire(object|string $event): void
    {
        $this->bus->dispatch($event);
    }

    /**
     * Register a listener.
     *
     * @param string $event           Event class/name
     * @param callable|array|string $listener  Listener
     * @param int $priority
     * @return void
     */
    public function listen(string $event, callable|array|string $listener, int $priority = 0): void
    {
        $this->bus->listen($event, $listener);
    }

    /**
     * Register a subscriber.
     *
     * @param EventSubscriberInterface|string $subscriber
     * @return void
     */
    public function subscribe(Subscriber|string $subscriber): void
    {
        $this->bus->subscribe($subscriber);
    }

    /**
     * Load listeners/subscribers from array.
     *
     * Example:
     * [
     *   'listeners' => [
     *       App\Events\UserRegistered::class => [
     *           [App\Listeners\SendWelcomeEmail::class, 'handle'],
     *           [App\Listeners\IncreaseUserStat::class, 'handle'],
     *       ],
     *   ],
     *   'subscribers' => [
     *       App\Listeners\UserEventSubscriber::class,
     *   ],
     * ]
     *
     * @param array $config
     * @return void
     */
    public function loadFromArray(): void
    {
        Config::load('event.php');

        // Get lists of Listeners
        if (Config::getArray('event.listeners') !== null) {
            foreach (Config::getArray('event.listeners') as $event => $listeners) {

                foreach ($listeners as $listener) {
                    $this->bus->listen($event, $listener);
                }
            }
        }

        // Get List of Subscribers
        if (Config::getArray('event.subscribers') !== null) {
            foreach (Config::getArray('event.subscribers') as $subscriber) {
                $this->bus->subscribe($subscriber);
            }
        }
    }
}
