<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Channels;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\KafkaPublisherInterface;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;

final class KafkaChannel implements NotificationChannelInterface
{
    public function __construct(private Application $app) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('Kafka notifications must implement NotificationInterface.');
        }

        $payload = $notification->toKafka($notifiable);
        $topic = (string) ($payload['topic'] ?? $config['topic'] ?? '');
        if ($topic === '') {
            throw new \InvalidArgumentException('Kafka notifications require a topic.');
        }

        $publisherService = $payload['publisher'] ?? $config['publisher'] ?? KafkaPublisherInterface::class;
        if (!is_string($publisherService) || $publisherService === '') {
            throw new \InvalidArgumentException('Kafka notifications require a publisher service name.');
        }

        $publisher = $this->app->make($publisherService);
        if (!$publisher instanceof KafkaPublisherInterface) {
            throw new \RuntimeException(sprintf(
                'Kafka publisher [%s] must implement %s.',
                $publisherService,
                KafkaPublisherInterface::class
            ));
        }

        $message = [
            'notification' => $notification::class,
            'notifiable' => $notifiable ? $notifiable::class : null,
            'payload' => $payload['payload'] ?? $payload,
        ];

        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        if (isset($payload['key'])) {
            $options['key'] = (string) $payload['key'];
        }
        if (isset($payload['headers']) && is_array($payload['headers'])) {
            $options['headers'] = $payload['headers'];
        }

        return $publisher->publish($topic, $message, $options);
    }
}
