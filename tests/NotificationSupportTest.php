<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Adapters\Event\NamedEvent;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\KafkaConsumerInterface;
use Marwa\Framework\Contracts\KafkaPublisherInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Notifications\Channels\BroadcastChannel;
use Marwa\Framework\Notifications\Channels\DatabaseChannel;
use Marwa\Framework\Notifications\Channels\HttpChannel;
use Marwa\Framework\Notifications\Channels\KafkaChannel;
use Marwa\Framework\Notifications\Channels\MailChannel;
use Marwa\Framework\Notifications\Channels\SmsChannel;
use Marwa\Framework\Notifications\Events\KafkaMessageReceived;
use Marwa\Framework\Notifications\Notification;
use Marwa\Framework\Notifications\NotificationManager;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\Http as HttpSupport;
use Marwa\Framework\Tests\Fixtures\Notifications\DemoNotifiable;
use Marwa\Framework\Tests\Fixtures\Notifications\DemoNotification;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingBroadcastNotificationChannel;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingEventDispatcher;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingHttpClient;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingHttpNotificationChannel;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingKafkaConsumer;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingKafkaPublisher;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingMailer;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingMailNotificationChannel;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingSmsNotificationChannel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class NotificationSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-notifications-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/database.php',
            <<<PHP
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'debug' => false,
        ],
    ],
];
PHP
        );
        file_put_contents(
            $this->basePath . '/config/notification.php',
            <<<'PHP'
<?php

return [
    'default' => ['mail', 'database', 'http', 'sms', 'broadcast'],
    'channels' => [
        'sms' => [
            'enabled' => true,
            'client' => 'default',
            'method' => 'POST',
            'url' => 'https://example.test/sms',
        ],
        'kafka' => [
            'enabled' => true,
            'topic' => 'notifications.orders',
            'consumer' => 'test.kafka.consumer',
        ],
    ],
];
PHP
        );
    }

    protected function tearDown(): void
    {
        @restore_error_handler();
        @restore_exception_handler();

        foreach ([
            $this->basePath . '/config/database.php',
            $this->basePath . '/config/notification.php',
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testNotificationManagerDispatchesAllConfiguredChannels(): void
    {
        $app = new \Marwa\Framework\Application($this->basePath);
        $mailer = new RecordingMailer();
        $http = new RecordingHttpClient();
        $events = new RecordingEventDispatcher();
        $kafka = new RecordingKafkaPublisher();
        $consumer = new RecordingKafkaConsumer();

        $app->set(HttpSupport::class, $http);
        $app->set(MailerInterface::class, $mailer);
        $app->set(HttpClientInterface::class, $http);
        $app->set(KafkaPublisherInterface::class, $kafka);
        $app->set('test.kafka.consumer', $consumer);
        $app->set(EventDispatcherInterface::class, $events);
        $app->set(MailChannel::class, new RecordingMailNotificationChannel($mailer));
        $app->set(HttpChannel::class, new RecordingHttpNotificationChannel($http));
        $app->set(SmsChannel::class, new RecordingSmsNotificationChannel($http));
        $app->set(BroadcastChannel::class, new RecordingBroadcastNotificationChannel($events));
        $app->set(KafkaChannel::class, new KafkaChannel($app));

        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $sql = <<<'SQL'
CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    notifiable_type TEXT NULL,
    notifiable_id TEXT NULL,
    type TEXT NOT NULL,
    channel TEXT NOT NULL,
    payload TEXT NOT NULL,
    read_at TEXT NULL,
    created_at TEXT NOT NULL
)
SQL;

        $manager->getPdo()->exec($sql);

        $notification = new DemoNotification(['mail', 'database', 'http', 'sms', 'kafka', 'broadcast']);
        $notifiable = new DemoNotifiable(7, 'user@example.com', '+15551234567');

        $results = $app->notifications()->send($notification, $notifiable);

        self::assertArrayHasKey('mail', $results);
        self::assertArrayHasKey('database', $results);
        self::assertArrayHasKey('http', $results);
        self::assertArrayHasKey('sms', $results);
        self::assertArrayHasKey('kafka', $results);
        self::assertArrayHasKey('broadcast', $results);

        self::assertSame([$notifiable->email, null], $mailer->state['to'] ?? null);
        self::assertSame('Hello', $mailer->state['subject'] ?? null);

        self::assertCount(2, $http->requests);
        self::assertSame('https://example.test/webhook', $http->requests[0]['uri']);
        self::assertSame('https://example.test/sms', $http->requests[1]['uri']);
        self::assertCount(1, $kafka->messages);
        self::assertSame('notifications.orders', $kafka->messages[0]['topic']);
        self::assertSame('order-1001', $kafka->messages[0]['options']['key'] ?? null);

        self::assertSame(1, (int) $manager->getPdo()->query('SELECT COUNT(*) FROM notifications')->fetchColumn());
        self::assertCount(1, $events->events);
        self::assertSame('notification.broadcasted', $events->events[0]->getName());
    }

    public function testNotificationHelperReturnsTheSharedManager(): void
    {
        $app = new \Marwa\Framework\Application($this->basePath);
        $http = new RecordingHttpClient();
        $kafka = new RecordingKafkaPublisher();

        $app->set(HttpSupport::class, $http);
        $app->set(HttpClientInterface::class, $http);
        $app->set(MailerInterface::class, new RecordingMailer());
        $app->set(KafkaPublisherInterface::class, $kafka);
        $app->set(EventDispatcherInterface::class, new RecordingEventDispatcher());
        $app->set(MailChannel::class, new RecordingMailNotificationChannel(new RecordingMailer()));
        $app->set(HttpChannel::class, new RecordingHttpNotificationChannel($http));
        $app->set(SmsChannel::class, new RecordingSmsNotificationChannel($http));
        $app->set(BroadcastChannel::class, new RecordingBroadcastNotificationChannel(new RecordingEventDispatcher()));
        $app->set(KafkaChannel::class, new KafkaChannel($app));
        $app->make(AppBootstrapper::class)->bootstrap();

        self::assertInstanceOf(NotificationManager::class, notification());
        self::assertSame($app->notifications(), notification());
    }

    public function testSharedNotificationManagerReadsUpdatedGlobalConfig(): void
    {
        $app = new \Marwa\Framework\Application($this->basePath);
        $http = new RecordingHttpClient();

        $app->set(HttpSupport::class, $http);
        $app->set(HttpClientInterface::class, $http);
        $app->set(MailerInterface::class, new RecordingMailer());
        $app->set(KafkaPublisherInterface::class, new RecordingKafkaPublisher());
        $app->set(EventDispatcherInterface::class, new RecordingEventDispatcher());
        $app->set(MailChannel::class, new RecordingMailNotificationChannel(new RecordingMailer()));
        $app->set(SmsChannel::class, new RecordingSmsNotificationChannel($http));
        $app->make(AppBootstrapper::class)->bootstrap();

        $manager = $app->notifications();
        $manager->configuration();

        /** @var Config $config */
        $config = $app->make(Config::class);
        $config->set('notification.default', ['sms']);
        $config->set('notification.channels.sms.enabled', true);

        $results = $manager->send(new DemoNotification([]), new DemoNotifiable(7, 'user@example.com', '+15551234567'));

        self::assertSame(['sms'], array_keys($results));
        self::assertSame('https://example.test/sms', $http->requests[0]['uri'] ?? null);
    }

    public function testDatabaseChannelRejectsUnsafeTableNames(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $channel = new DatabaseChannel($manager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid notifications table name');

        $channel->send(new class () extends Notification {
            public function toDatabase(?object $notifiable = null): array
            {
                return [
                    'table' => 'notifications; DROP TABLE users',
                    'payload' => [
                        'message' => 'unsafe',
                    ],
                ];
            }
        });
    }

    public function testHttpChannelRejectsNonHttpUrls(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $http = new RecordingHttpClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP notifications require an http or https url.');

        (new HttpChannel($http))->send(new class () extends Notification {
            public function toHttp(?object $notifiable = null): array
            {
                return [
                    'url' => 'file:///etc/passwd',
                ];
            }
        });
    }

    public function testSmsChannelRejectsNonHttpUrls(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $http = new RecordingHttpClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMS notifications require an http or https url.');

        (new SmsChannel($http))->send(new class () extends Notification {
            public function toSms(?object $notifiable = null): array
            {
                return [
                    'url' => 'file:///etc/passwd',
                ];
            }
        });
    }

    public function testBroadcastChannelFallsBackWhenConfiguredEventIsNotNamedEvent(): void
    {
        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $events = new RecordingEventDispatcher();
        $channel = new BroadcastChannel($events);

        $result = $channel->send(new DemoNotification(['broadcast']), null, [
            'event' => \DateTimeImmutable::class,
        ]);

        self::assertInstanceOf(NamedEvent::class, $result);
        self::assertSame('notification.broadcasted', $result->getName());
        self::assertSame($result, $events->events[0] ?? null);
    }

    public function testKafkaChannelPublishesMessagesThroughConfiguredPublisher(): void
    {
        $app = new \Marwa\Framework\Application($this->basePath);
        $kafka = new RecordingKafkaPublisher();

        $app->set(KafkaPublisherInterface::class, $kafka);
        $app->set(KafkaChannel::class, new KafkaChannel($app));
        $app->make(AppBootstrapper::class)->bootstrap();

        $results = $app->notifications()->send(new DemoNotification(['kafka']), new DemoNotifiable(7, 'user@example.com', '+15551234567'));

        self::assertArrayHasKey('kafka', $results);
        self::assertCount(1, $kafka->messages);
        self::assertSame('notifications.orders', $kafka->messages[0]['topic']);
        self::assertSame('kafka message', $kafka->messages[0]['message']['payload']['message'] ?? null);
    }

    public function testKafkaConsumeCommandDispatchesReceivedMessages(): void
    {
        file_put_contents(
            $this->basePath . '/config/notification.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'channels' => [
        'kafka' => [
            'enabled' => true,
            'consumer' => Marwa\Framework\Contracts\KafkaConsumerInterface::class,
            'topics' => ['orders.created'],
            'groupId' => 'integration-tests',
        ],
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $consumer = new RecordingKafkaConsumer();
        $events = [];

        $app->set(KafkaConsumerInterface::class, $consumer);
        $app->make(AppBootstrapper::class)->bootstrap();
        $app->make(EventDispatcherInterface::class)->listen(
            KafkaMessageReceived::class,
            function (KafkaMessageReceived $event) use (&$events): void {
                $events[] = $event;
            }
        );

        $console = $app->console()->application();
        $tester = new CommandTester($console->find('kafka:consume'));

        $status = $tester->execute([
            '--once' => true,
            '--json' => true,
        ]);

        self::assertSame(0, $status);
        self::assertCount(1, $consumer->consumed);
        self::assertCount(1, $events);
        self::assertSame('kafka.message.received', $events[0]->getName());
        self::assertStringContainsString('"topic":"orders.created"', $tester->getDisplay());
    }
}
