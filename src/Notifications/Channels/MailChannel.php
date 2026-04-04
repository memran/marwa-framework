<?php

declare(strict_types=1);

namespace Marwa\Framework\Notifications\Channels;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;

final class MailChannel implements NotificationChannelInterface
{
    public function __construct(private MailerInterface $mailer) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('Mail notifications must implement NotificationInterface.');
        }

        $message = $notification->toMail($notifiable);
        $mailer = $this->mailer->reset();

        $from = $message['from'] ?? null;
        if (is_array($from) && isset($from['address'])) {
            $mailer->from((string) $from['address'], isset($from['name']) ? (string) $from['name'] : null);
        }

        $recipients = $message['to'] ?? $this->resolveRecipient($notifiable, 'mail');
        if (is_string($recipients)) {
            $mailer->to($recipients);
        } elseif (is_array($recipients)) {
            $mailer->to($recipients);
        }

        if (isset($message['cc']) && is_array($message['cc'])) {
            $mailer->cc($message['cc']);
        }

        if (isset($message['bcc']) && is_array($message['bcc'])) {
            $mailer->bcc($message['bcc']);
        }

        if (isset($message['replyTo']) && is_array($message['replyTo'])) {
            $mailer->replyTo($message['replyTo']);
        }

        if (isset($message['subject'])) {
            $mailer->subject((string) $message['subject']);
        }

        if (isset($message['html'])) {
            $mailer->html((string) $message['html'], isset($message['text']) ? (string) $message['text'] : null);
        } elseif (isset($message['text'])) {
            $mailer->text((string) $message['text']);
        }

        foreach ($message['attachments'] ?? [] as $attachment) {
            if (!is_array($attachment) || !isset($attachment['path'])) {
                continue;
            }

            $mailer->attach(
                (string) $attachment['path'],
                isset($attachment['name']) ? (string) $attachment['name'] : null,
                isset($attachment['mime']) ? (string) $attachment['mime'] : 'application/octet-stream'
            );
        }

        return $mailer->send();
    }

    private function resolveRecipient(?object $notifiable, string $channel): mixed
    {
        if ($notifiable === null) {
            return null;
        }

        $method = 'routeNotificationFor' . ucfirst($channel);
        if (method_exists($notifiable, $method)) {
            return $notifiable->{$method}();
        }

        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor($channel);
        }

        return property_exists($notifiable, 'email') ? $notifiable->email : null;
    }
}
