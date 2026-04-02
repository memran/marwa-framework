<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Modules\Blog;

use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class BlogModuleServiceProvider implements ModuleServiceProviderInterface
{
    public static int $registerCalls = 0;
    public static int $bootCalls = 0;

    public function register($app): void
    {
        self::$registerCalls++;
        $app->set('module.blog.registered', true);
    }

    public function boot($app): void
    {
        self::$bootCalls++;
        $app->set('module.blog.booted', true);
    }
}
