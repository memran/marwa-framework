<?php

declare(strict_types=1);

namespace Marwa\Framework\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;

final class ConsoleServiceProvider extends ServiceProviderAdapter
{
    public function provides(string $id): bool
    {
        return $id === 'console.commands';
    }

    public function register(): void
    {
        $this->getContainer()->add('console.commands', []);
    }
}
