<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Modules\User;

use Marwa\Framework\Application;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class UserModuleServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if ($app instanceof Application) {
            $app->set('module.user.registered', true);
        }
    }

    public function boot($app): void
    {
        if ($app instanceof Application) {
            $app->set('module.user.booted', true);
        }
    }
}
