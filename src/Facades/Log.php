<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Facades\Facade;

final class Log extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LoggerAdapter::class;
    }
}
