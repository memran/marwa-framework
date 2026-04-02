<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Supports\Storage as StorageManager;

final class Storage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StorageManager::class;
    }
}
