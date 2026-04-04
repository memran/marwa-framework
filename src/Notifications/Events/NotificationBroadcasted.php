<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Events;

use Marwa\Framework\Adapters\Event\NamedEvent;

final class NotificationBroadcasted extends NamedEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(string $name = 'notification.broadcasted', array $payload = [])
    {
        parent::__construct($name, $payload);
    }
}
