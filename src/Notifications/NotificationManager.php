<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications;

use Marwa\Framework\Application;
use Marwa\Framework\Config\NotificationConfig;
use Marwa\Framework\Contracts\NotificationInterface;
use Marwa\Framework\Notifications\Channels\BroadcastChannel;
use Marwa\Framework\Notifications\Channels\DatabaseChannel;
use Marwa\Framework\Notifications\Channels\HttpChannel;
use Marwa\Framework\Notifications\Channels\MailChannel;
use Marwa\Framework\Notifications\Channels\SmsChannel;
use Marwa\Framework\Supports\Config;

final class NotificationManager
{
    /**
     * @var array{
     *     enabled: bool,
     *     default: list<string>,
     *     channels: array<string, array<string, mixed>>
     * }
     */
    private array $settings;

    public function __construct(
        private Application $app,
        private Config $config
    ) {
        $this->config->loadIfExists(NotificationConfig::KEY . '.php');
        $this->settings = NotificationConfig::merge($this->app, $this->config->getArray(NotificationConfig::KEY, []));
    }

    /**
     * @return array{
     *     enabled: bool,
     *     default: list<string>,
     *     channels: array<string, array<string, mixed>>
     * }
     */
    public function configuration(): array
    {
        return $this->settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function send(NotificationInterface $notification, ?object $notifiable = null): array
    {
        if (!$this->settings['enabled']) {
            return [];
        }

        $channels = $notification->via($notifiable);
        if ($channels === []) {
            $channels = $this->settings['default'];
        }

        $results = [];

        foreach ($channels as $channel) {
            $config = $this->settings['channels'][$channel] ?? ['enabled' => false];

            if (!(bool) ($config['enabled'] ?? false)) {
                continue;
            }

            $results[$channel] = $this->sendThroughChannel($channel, $notification, $notifiable, $config);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function sendThroughChannel(string $channel, NotificationInterface $notification, ?object $notifiable, array $config): mixed
    {
        return match ($channel) {
            'mail' => $this->app->make(MailChannel::class)->send($notification, $notifiable, $config),
            'database' => $this->app->make(DatabaseChannel::class)->send($notification, $notifiable, $config),
            'http' => $this->app->make(HttpChannel::class)->send($notification, $notifiable, $config),
            'sms' => $this->app->make(SmsChannel::class)->send($notification, $notifiable, $config),
            'broadcast' => $this->app->make(BroadcastChannel::class)->send($notification, $notifiable, $config),
            default => throw new \InvalidArgumentException(sprintf('Notification channel [%s] is not supported.', $channel)),
        };
    }
}
