<?php

declare(strict_types=1);

namespace Marwa\App\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Marwa\App\Events\EventManager;
use Marwa\App\Events\EventAutoRegistrar;

/**
 * Tiny League\Container service provider.
 *
 * Binds:
 * - EventDispatcher (league/event)
 * - EventManager (our Laravel-flavored wrapper)
 * - Auto-registers listeners/subscribers from config ['events' => ...]
 */
final class EventServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{

    public function provides(string $id): bool
    {
        $services = [
            EventManager::class,
        ];

        return in_array($id, $services);
    }
    public function register(): void
    {
        dd("Loading Event Service Provider");
        $container = $this->getContainer();

        // Our EventManager
        $container->addShared(EventManager::class, function (): EventManager {
            return new EventManager();
        });
    }

    public function boot(): void
    {

        $this->getContainer()->get(EventManager::class)->register(config('event.listeners'));
    }
}
