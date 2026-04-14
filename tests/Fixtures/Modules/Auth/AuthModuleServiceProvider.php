<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Modules\Auth;

use Marwa\Framework\Application;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class AuthModuleServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if ($app instanceof Application) {
            $app->set('module.auth.registered', true);
        }
    }

    public function boot($app): void
    {
        if ($app instanceof Application) {
            $app->set('module.auth.booted', true);
        }
    }
}
