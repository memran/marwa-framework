<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Channels;

use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;

final class HttpChannel implements NotificationChannelInterface
{
    public function __construct(private HttpClientInterface $http) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('HTTP notifications must implement NotificationInterface.');
        }

        $payload = $notification->toHttp($notifiable);
        $client = $this->http;

        if (isset($payload['client']) && is_string($payload['client']) && $payload['client'] !== '') {
            $client = $client->withClient($payload['client']);
        } elseif (isset($config['client']) && is_string($config['client']) && $config['client'] !== '') {
            $client = $client->withClient($config['client']);
        }

        $method = strtoupper((string) ($payload['method'] ?? $config['method'] ?? 'POST'));
        $uri = (string) ($payload['url'] ?? $config['url'] ?? '');
        if ($uri === '') {
            throw new \InvalidArgumentException('HTTP notifications require a url.');
        }
        $this->assertHttpUrl($uri);

        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        if (isset($config['headers']) && is_array($config['headers'])) {
            $options['headers'] = array_replace($config['headers'], is_array($options['headers'] ?? null) ? $options['headers'] : []);
        }
        if (isset($payload['headers']) && is_array($payload['headers'])) {
            $options['headers'] = array_replace(is_array($options['headers'] ?? null) ? $options['headers'] : [], $payload['headers']);
        }

        if (array_key_exists('json', $payload)) {
            $options['json'] = $payload['json'];
        } elseif (array_key_exists('body', $payload)) {
            $options['body'] = $payload['body'];
        } elseif (array_key_exists('payload', $payload) && is_array($payload['payload'])) {
            $options['json'] = $payload['payload'];
        }

        return $client->request($method, $uri, $options);
    }

    private function assertHttpUrl(string $uri): void
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === 'http' || $scheme === 'https') {
            return;
        }

        throw new \InvalidArgumentException('HTTP notifications require an http or https url.');
    }
}
