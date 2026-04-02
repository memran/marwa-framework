<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Event;

use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\Subscriber;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Resolver\ListenerResolver;
use Marwa\Framework\Config\EventConfig;
use Marwa\Framework\Supports\Config;
use Psr\Container\ContainerInterface;

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

    public function __construct(ContainerInterface $container, private Config $config)
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

    public function fire(object|string $event): void
    {
        $this->bus->dispatch($event);
    }

    /**
     * @param callable|array<int|string, mixed>|string $listener
     */
    public function listen(string $event, callable|array|string $listener, int $priority = 0): void
    {
        $this->bus->listen($event, $listener);
    }

    public function subscribe(Subscriber|string $subscriber): void
    {
        $this->bus->subscribe($subscriber);
    }

    public function loadFromArray(): void
    {
        $this->config->loadIfExists(EventConfig::KEY . '.php');

        /** @var array<string, list<callable|array<int|string, mixed>|string>> $listenersByEvent */
        $listenersByEvent = $this->config->getArray(EventConfig::KEY . '.listeners', EventConfig::defaults()['listeners']);

        foreach ($listenersByEvent as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->bus->listen($event, $listener);
            }
        }

        /** @var list<Subscriber|string> $subscribers */
        $subscribers = $this->config->getArray(EventConfig::KEY . '.subscribers', EventConfig::defaults()['subscribers']);

        foreach ($subscribers as $subscriber) {
            $this->bus->subscribe($subscriber);
        }
    }
}
