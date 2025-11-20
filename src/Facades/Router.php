<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Facades\Facade;

final class Router extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RouterAdapter::class;
    }
}
