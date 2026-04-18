<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Authorization\Contracts\GateInterface;

final class Gate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GateInterface::class;
    }
}
