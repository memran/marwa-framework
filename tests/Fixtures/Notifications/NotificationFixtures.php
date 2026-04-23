<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Notifications;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Marwa\Framework\Adapters\Event\NamedEvent;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\KafkaConsumerInterface;
use Marwa\Framework\Contracts\KafkaPublisherInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Contracts\NotificationChannelInterface;
use Marwa\Framework\Contracts\NotificationInterface;
use Marwa\Framework\Notifications\Notifiable;
use Marwa\Framework\Notifications\Notification;
use Marwa\Framework\Queue\QueuedJob;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RecordingMailer implements MailerInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $state = [];

    public function configuration(): array
    {
        return [
            'enabled' => true,
            'driver' => 'smtp',
            'charset' => 'UTF-8',
            'from' => [
                'address' => 'no-reply@example.com',
                'name' => 'MarwaPHP',
            ],
            'smtp' => [
                'host' => '127.0.0.1',
                'port' => 1025,
                'encryption' => null,
                'username' => null,
                'password' => null,
                'authMode' => null,
                'timeout' => 30,
            ],
            'sendmail' => [
                'path' => '/usr/sbin/sendmail -bs',
            ],
        ];
    }

    public function reset(): self
    {
        $this->state = [];

        return $this;
    }

    public function from(string|array $address, ?string $name = null): self
    {
        $this->state['from'] = [$address, $name];

        return $this;
    }

    public function to(string|array $address, ?string $name = null): self
    {
        $this->state['to'] = [$address, $name];

        return $this;
    }

    public function cc(string|array $address, ?string $name = null): self
    {
        $this->state['cc'] = [$address, $name];

        return $this;
    }

    public function bcc(string|array $address, ?string $name = null): self
    {
        $this->state['bcc'] = [$address, $name];

        return $this;
    }

    public function replyTo(string|array $address, ?string $name = null): self
    {
        $this->state['replyTo'] = [$address, $name];

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->state['subject'] = $subject;

        return $this;
    }

    public function text(string $text): self
    {
        $this->state['text'] = $text;

        return $this;
    }

    public function html(string $html, ?string $text = null): self
    {
        $this->state['html'] = $html;
        $this->state['html_text'] = $text;

        return $this;
    }

    public function attach(string $path, ?string $name = null, string $mime = 'application/octet-stream'): self
    {
        $this->state['attachments'][] = compact('path', 'name', 'mime');

        return $this;
    }

    public function attachData(string $data, string $name, string $mime = 'application/octet-stream'): self
    {
        $this->state['attachments'][] = compact('data', 'name', 'mime');

        return $this;
    }

    public function message(): object
    {
        return (object) $this->state;
    }

    public function transport(): object
    {
        return (object) [];
    }

    public function send(?callable $callback = null): int
    {
        $this->state['sent'] = true;

        return 1;
    }

    public function queue(\Marwa\Framework\Mail\Mailable $mailable, ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        return QueuedJob::fromArray([
            'id' => bin2hex(random_bytes(8)),
            'name' => 'mail:send',
            'queue' => $queue ?? 'default',
            'payload' => ['mailable' => $mailable::class],
            'attempts' => 0,
            'availableAt' => time() + max(0, $delaySeconds),
            'createdAt' => time(),
        ]);
    }

    public function queueAt(\Marwa\Framework\Mail\Mailable $mailable, ?string $queue = null, int $timestamp): QueuedJob
    {
        return QueuedJob::fromArray([
            'id' => bin2hex(random_bytes(8)),
            'name' => 'mail:send',
            'queue' => $queue ?? 'default',
            'payload' => ['mailable' => $mailable::class],
            'attempts' => 0,
            'availableAt' => $timestamp,
            'createdAt' => time(),
        ]);
    }

    public function queueRecurring(\Marwa\Framework\Mail\Mailable $mailable, ?string $queue = null, array $schedule): QueuedJob
    {
        return QueuedJob::fromArray([
            'id' => bin2hex(random_bytes(8)),
            'name' => 'mail:send',
            'queue' => $queue ?? 'default',
            'payload' => ['mailable' => $mailable::class, '_recurring' => $schedule],
            'attempts' => 0,
            'availableAt' => time(),
            'createdAt' => time(),
        ]);
    }
}

final class RecordingHttpClient implements HttpClientInterface
{
    /**
     * @var array{
     *     enabled: bool,
     *     default: string,
     *     clients: array<string, array<string, mixed>>
     * }
     */
    private array $configuration;

    /**
     * @var list<array{method: string, uri: string, options: array<string, mixed>}>
     */
    public array $requests = [];

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration ?: [
            'enabled' => true,
            'default' => 'default',
            'clients' => [
                'default' => [
                    'base_uri' => null,
                    'timeout' => 30.0,
                    'connect_timeout' => 10.0,
                    'http_errors' => false,
                    'verify' => true,
                    'headers' => [],
                ],
            ],
        ];
    }

    public function configuration(): array
    {
        return $this->configuration;
    }

    public function client(?string $name = null): \GuzzleHttp\ClientInterface
    {
        return new Client();
    }

    public function withClient(string $name): self
    {
        return $this;
    }

    public function withOptions(array $options): self
    {
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        return $this;
    }

    public function header(string $name, string $value): self
    {
        return $this;
    }

    public function token(string $token, string $type = 'Bearer'): self
    {
        return $this;
    }

    public function baseUri(string $uri): self
    {
        return $this;
    }

    public function timeout(int|float $seconds): self
    {
        return $this;
    }

    public function connectTimeout(int|float $seconds): self
    {
        return $this;
    }

    public function verify(bool|string $verify): self
    {
        return $this;
    }

    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $this->requests[] = compact('method', 'uri', 'options');

        return new Response(200, [], 'ok');
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $this->requests[] = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'options' => $options,
        ];

        return new Response(200, [], (string) $request->getBody());
    }

    public function get(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    public function post(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    public function put(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    public function patch(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    public function delete(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    public function head(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    public function options(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('OPTIONS', $uri, $options);
    }

    public function json(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface
    {
        $options['json'] = $payload;

        return $this->request($method, $uri, $options);
    }

    public function form(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface
    {
        $options['form_params'] = $payload;

        return $this->request($method, $uri, $options);
    }

    public function multipart(string $method, string $uri = '', array $parts = [], array $options = []): ResponseInterface
    {
        $options['multipart'] = $parts;

        return $this->request($method, $uri, $options);
    }
}

final class RecordingHttpNotificationChannel implements NotificationChannelInterface
{
    public function __construct(private RecordingHttpClient $http) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('HTTP notifications must implement NotificationInterface.');
        }

        $payload = $notification->toHttp($notifiable);
        $method = strtoupper((string) ($payload['method'] ?? $config['method'] ?? 'POST'));
        $uri = (string) ($payload['url'] ?? $config['url'] ?? '');
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];

        if (isset($payload['json'])) {
            $options['json'] = $payload['json'];
        } elseif (isset($payload['body'])) {
            $options['body'] = $payload['body'];
        } elseif (isset($payload['payload']) && is_array($payload['payload'])) {
            $options['json'] = $payload['payload'];
        }

        return $this->http->request($method, $uri, $options);
    }
}

final class RecordingMailNotificationChannel implements NotificationChannelInterface
{
    public function __construct(private RecordingMailer $mailer) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('Mail notifications must implement NotificationInterface.');
        }

        $message = $notification->toMail($notifiable);
        $mailer = $this->mailer->reset();

        if (isset($message['to'])) {
            $mailer->to($message['to']);
        } elseif ($notifiable instanceof DemoNotifiable) {
            $mailer->to($notifiable->email);
        }

        if (isset($message['subject'])) {
            $mailer->subject((string) $message['subject']);
        }

        if (isset($message['text'])) {
            $mailer->text((string) $message['text']);
        }

        if (isset($message['html'])) {
            $mailer->html((string) $message['html'], isset($message['text']) ? (string) $message['text'] : null);
        }

        return $mailer->send();
    }
}

final class RecordingSmsNotificationChannel implements NotificationChannelInterface
{
    public function __construct(private RecordingHttpClient $http) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('SMS notifications must implement NotificationInterface.');
        }

        $payload = $notification->toSms($notifiable);
        $method = strtoupper((string) ($payload['method'] ?? $config['method'] ?? 'POST'));
        $uri = (string) ($payload['url'] ?? $config['url'] ?? '');

        return $this->http->request($method, $uri, [
            'json' => [
                'to' => $payload['to'] ?? null,
                'message' => (string) ($payload['message'] ?? ''),
                'meta' => $payload['meta'] ?? [],
            ],
        ]);
    }
}

final class RecordingBroadcastNotificationChannel implements NotificationChannelInterface
{
    public function __construct(private RecordingEventDispatcher $events) {}

    public function send(object $notification, ?object $notifiable = null, array $config = []): mixed
    {
        if (!$notification instanceof NotificationInterface) {
            throw new \InvalidArgumentException('Broadcast notifications must implement NotificationInterface.');
        }

        $payload = $notification->toBroadcast($notifiable);
        $eventName = (string) ($payload['event'] ?? 'notification.broadcasted');
        $event = new NamedEvent($eventName, [
            'notification' => $notification::class,
            'notifiable' => $notifiable ? $notifiable::class : null,
            'payload' => $payload['payload'] ?? $payload,
        ]);

        return $this->events->dispatch($event);
    }
}

final class RecordingKafkaPublisher implements KafkaPublisherInterface
{
    /**
     * @var list<array{topic: string, message: array<string, mixed>, options: array<string, mixed>}>
     */
    public array $messages = [];

    public function publish(string $topic, array $message, array $options = []): mixed
    {
        $this->messages[] = compact('topic', 'message', 'options');

        return [
            'topic' => $topic,
            'message' => $message,
            'options' => $options,
        ];
    }
}

final class RecordingKafkaConsumer implements KafkaConsumerInterface
{
    /**
     * @var list<array{topics: list<string>, message: array<string, mixed>, options: array<string, mixed>}>
     */
    public array $consumed = [];

    /**
     * @param list<string> $topics
     * @param callable(array<string, mixed>, string): mixed $handler
     * @param array<string, mixed> $options
     */
    public function consume(array $topics, callable $handler, array $options = []): int
    {
        $message = [
            'offset' => 1,
            'partition' => 0,
            'key' => 'order-1001',
            'payload' => [
                'message' => 'kafka consumed message',
            ],
        ];

        foreach ($topics as $topic) {
            $this->consumed[] = [
                'topics' => $topics,
                'message' => $message,
                'options' => $options,
            ];

            $handler($message, $topic);
        }

        return count($topics);
    }
}

final class RecordingEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var list<object>
     */
    public array $events = [];

    /**
     * @var array<string, list<array{listener: callable|array<int|string, mixed>|string, priority: int}>>
     */
    public array $listeners = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }

    public function listen(string $event, callable|array|string $listener, int $priority = 0): void
    {
        $this->listeners[$event][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];
    }
}

final class DemoNotifiable
{
    use Notifiable;

    public function __construct(
        public int $id,
        public string $email,
        public string $phone
    ) {}

    public function routeNotificationForDatabase(): int
    {
        return $this->id;
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function routeNotificationForSms(): string
    {
        return $this->phone;
    }
}

final class DemoNotification extends Notification
{
    /**
     * @param list<string> $channels
     */
    public function __construct(private array $channels = ['mail']) {}

    public function via(?object $notifiable = null): array
    {
        return $this->channels;
    }

    public function toMail(?object $notifiable = null): array
    {
        return [
            'to' => $notifiable instanceof DemoNotifiable ? $notifiable->email : 'user@example.com',
            'subject' => 'Hello',
            'text' => 'Hi there',
        ];
    }

    public function toDatabase(?object $notifiable = null): array
    {
        return [
            'payload' => [
                'message' => 'saved',
            ],
        ];
    }

    public function toHttp(?object $notifiable = null): array
    {
        return [
            'client' => 'default',
            'method' => 'POST',
            'url' => 'https://example.test/webhook',
            'json' => [
                'message' => 'webhook',
            ],
        ];
    }

    public function toSms(?object $notifiable = null): array
    {
        return [
            'client' => 'default',
            'method' => 'POST',
            'url' => 'https://example.test/sms',
            'to' => $notifiable instanceof DemoNotifiable ? $notifiable->phone : '+100000000',
            'message' => 'sms message',
        ];
    }

    public function toKafka(?object $notifiable = null): array
    {
        return [
            'topic' => 'notifications.orders',
            'key' => 'order-1001',
            'headers' => [
                'x-channel' => 'kafka',
            ],
            'payload' => [
                'message' => 'kafka message',
            ],
        ];
    }

    public function toBroadcast(?object $notifiable = null): array
    {
        return [
            'event' => 'notification.broadcasted',
            'payload' => [
                'message' => 'broadcast',
            ],
        ];
    }
}
