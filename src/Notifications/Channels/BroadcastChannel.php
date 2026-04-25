<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Channels;

use Marwa\Framework\Adapters\Event\NamedEvent;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;

final class BroadcastChannel implements NotificationChannelInterface
{
    public function __construct(private EventDispatcherInterface $events) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('Broadcast notifications must implement NotificationInterface.');
        }

        $payload = $notification->toBroadcast($notifiable);
        $eventName = (string) ($payload['event'] ?? 'notification.broadcasted');
        $eventClass = is_string($config['event'] ?? null) && is_a($config['event'], NamedEvent::class, true)
            ? $config['event']
            : NamedEvent::class;

        $event = new $eventClass($eventName, [
            'notification' => $notification::class,
            'notifiable' => $notifiable ? $notifiable::class : null,
            'payload' => $payload['payload'] ?? $payload,
        ]);

        return $this->events->dispatch($event);
    }
}
