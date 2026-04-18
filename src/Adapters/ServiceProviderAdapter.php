<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use League\Container\ServiceProvider\AbstractServiceProvider;

abstract class ServiceProviderAdapter extends AbstractServiceProvider
{
    /**
     * Called after provider is registered but before boot.
     * Called during bootstrap before providers are booted.
     */
    public function registered(): void {}

    /**
     * Called right before providers are booted.
     * This is called once for all providers before any boot() method.
     */
    public function booting(): void {}

    /**
     * Called after this provider is booted.
     * Called after individual provider's boot() completes.
     */
    public function booted(): void {}
}
