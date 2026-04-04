<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications;

use Marwa\Framework\Contracts\NotificationInterface;

abstract class Notification implements NotificationInterface
{
    public function via(?object $notifiable = null): array
    {
        return [];
    }

    public function toMail(?object $notifiable = null): array
    {
        return [];
    }

    public function toDatabase(?object $notifiable = null): array
    {
        return [];
    }

    public function toHttp(?object $notifiable = null): array
    {
        return [];
    }

    public function toSms(?object $notifiable = null): array
    {
        return [];
    }

    public function toKafka(?object $notifiable = null): array
    {
        return [];
    }

    public function toBroadcast(?object $notifiable = null): array
    {
        return [];
    }
}
