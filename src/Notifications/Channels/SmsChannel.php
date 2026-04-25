<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Channels;

use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;

final class SmsChannel implements NotificationChannelInterface
{
    public function __construct(private HttpClientInterface $http) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('SMS notifications must implement NotificationInterface.');
        }

        $payload = $notification->toSms($notifiable);
        $uri = (string) ($payload['url'] ?? $config['url'] ?? '');
        if ($uri === '') {
            throw new \InvalidArgumentException('SMS notifications require a url.');
        }
        $this->assertHttpUrl($uri);

        $method = strtoupper((string) ($payload['method'] ?? $config['method'] ?? 'POST'));
        $options = [
            'json' => [
                'to' => $payload['to'] ?? $this->resolveRecipient($notifiable),
                'message' => (string) ($payload['message'] ?? ''),
                'meta' => $payload['meta'] ?? [],
            ],
        ];

        if (isset($payload['client']) && is_string($payload['client']) && $payload['client'] !== '') {
            return $this->http->withClient($payload['client'])->request($method, $uri, $options);
        }

        if (isset($config['client']) && is_string($config['client']) && $config['client'] !== '') {
            return $this->http->withClient($config['client'])->request($method, $uri, $options);
        }

        return $this->http->request($method, $uri, $options);
    }

    private function resolveRecipient(?object $notifiable): mixed
    {
        if ($notifiable === null) {
            return null;
        }

        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms();
        }

        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('sms');
        }

        return property_exists($notifiable, 'phone') ? $notifiable->phone : (property_exists($notifiable, 'phone_number') ? $notifiable->phone_number : null);
    }

    private function assertHttpUrl(string $uri): void
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === 'http' || $scheme === 'https') {
            return;
        }

        throw new \InvalidArgumentException('SMS notifications require an http or https url.');
    }
}
