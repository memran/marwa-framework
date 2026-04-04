<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Contracts\SecurityInterface;

final class Security extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SecurityInterface::class;
    }
}
