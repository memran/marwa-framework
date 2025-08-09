<?php
namespace Marwa\App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class AppServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    protected $provides = []; // optional; empty means always eager

    public function register(): void
    {
        // bindings here
    }

    public function boot(): void
    {
        // post-registration actions here
    }
}
