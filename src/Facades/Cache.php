<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Facades\Facade;
use Marwa\Framework\Contracts\CacheInterface;

final class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CacheInterface::class;
    }
}
