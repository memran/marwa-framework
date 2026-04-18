<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Authorization\AuthManager;

final class Auth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthManager::class;
    }
}
