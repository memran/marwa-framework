<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Adapters\RouterAdapter;

final class Router extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RouterAdapter::class;
    }
}
