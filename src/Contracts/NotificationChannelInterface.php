<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface NotificationChannelInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed;
}
