<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Database\DBForge;

final class DbForge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DBForge::class;
    }
}