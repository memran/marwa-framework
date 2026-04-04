<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications;

use Marwa\Framework\Contracts\NotificationInterface;

trait Notifiable
{
    /**
     * @return array<string, mixed>
     */
    public function notify(NotificationInterface $notification): array
    {
        return notification()->send($notification, $this);
    }

    public function routeNotificationFor(string $channel): mixed
    {
        return null;
    }
}
