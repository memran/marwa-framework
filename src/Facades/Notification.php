<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Notifications\NotificationManager;

final class Notification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationManager::class;
    }
}
