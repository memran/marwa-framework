<?php

declare(strict_types=1);

namespace Marwa\App\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Marwa\App\Filesystem\FilesystemManager;

/**
 * Tiny League\Container service provider.
 *
 * Binds:
 * - EventDispatcher (league/event)
 * - EventManager (our Laravel-flavored wrapper)
 * - Auto-registers listeners/subscribers from config ['events' => ...]
 */
final class FilesystemServiceProvider extends AbstractServiceProvider
{

    public function provides(string $id): bool
    {
        $services = [
            'storage',
            'file'
        ];

        return in_array($id, $services);
    }
    public function register(): void
    {

        $container = $this->getContainer();

        // Our Filemanager
        $container->addShared('storage', function () {
            return new FilesystemManager(config('file'));
        });

        $container->addShared('file', function () {
            return (new FilesystemManager(config('file')))->disk();
        });
    }
}
