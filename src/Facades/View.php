<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Framework\Facades\Facade;

final class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {

        return ViewAdapter::class;
    }
}
