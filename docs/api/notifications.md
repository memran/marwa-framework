# Notifications API

## `Marwa\Framework\Notifications\NotificationManager`

### `configuration(): array`

Returns the merged notification configuration.

### `send(NotificationInterface $notification, ?object $notifiable = null): array`

Sends a notification through the configured channels and returns channel results keyed by channel name.

## `Marwa\Framework\Notifications\Notification`

Base class for application notifications. Override `via()`, `toMail()`, `toDatabase()`, `toHttp()`, `toSms()`, and `toBroadcast()`.
Add `toKafka()` when you want to publish a payload through the Kafka channel.

## `Marwa\Framework\Notifications\Notifiable`

Trait that adds `notify()` to application models or entities.

## Channel payloads

- `mail`: `to`, `subject`, `text`, `html`, `from`, `cc`, `bcc`, `replyTo`, `attachments`
- `database`: `payload`, `notifiable_type`, `notifiable_id`, `table`, `connection`
- `http`: `client`, `method`, `url`, `headers`, `json`, `body`, `options`
- `sms`: `client`, `method`, `url`, `to`, `message`, `meta`
- `kafka`: `publisher`, `topic`, `key`, `headers`, `options`, `payload`
- `broadcast`: `event`, `payload`
