<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Channels;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;

final class DatabaseChannel implements NotificationChannelInterface
{
    public function __construct(private ConnectionManager $manager) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('Database notifications must implement NotificationInterface.');
        }

        $payload = $notification->toDatabase($notifiable);
        $connectionName = (string) ($payload['connection'] ?? $config['connection'] ?? 'default');
        $connection = $this->manager->getPdo($connectionName);
        $table = (string) ($payload['table'] ?? $config['table'] ?? 'notifications');

        $data = [
            'notifiable_type' => $payload['notifiable_type'] ?? ($notifiable ? $notifiable::class : null),
            'notifiable_id' => $payload['notifiable_id'] ?? $this->resolveNotifiableId($notifiable),
            'type' => $payload['type'] ?? $notification::class,
            'channel' => 'database',
            'payload' => json_encode($payload['payload'] ?? $payload, JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $statement = $connection->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        ));

        foreach ($data as $column => $value) {
            $statement->bindValue(':' . $column, $value);
        }

        $statement->execute();

        return $data;
    }

    private function resolveNotifiableId(?object $notifiable): mixed
    {
        if ($notifiable === null) {
            return null;
        }

        if (method_exists($notifiable, 'routeNotificationForDatabase')) {
            return $notifiable->routeNotificationForDatabase();
        }

        if (method_exists($notifiable, 'getKey')) {
            return $notifiable->getKey();
        }

        return property_exists($notifiable, 'id') ? $notifiable->id : null;
    }
}
