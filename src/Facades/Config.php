<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Facades\Facade;
use Marwa\Framework\Supports\Config as ConfigClass;

final class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {

        return ConfigClass::class;
    }
}
