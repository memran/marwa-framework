<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;

final class CountingServiceProvider extends ServiceProviderAdapter
{
    public static int $registerCalls = 0;

    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
        self::$registerCalls++;
    }
}
