<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\KafkaPublisherInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Notifications\Channels\BroadcastChannel;
use Marwa\Framework\Notifications\Channels\HttpChannel;
use Marwa\Framework\Notifications\Channels\KafkaChannel;
use Marwa\Framework\Notifications\Channels\MailChannel;
use Marwa\Framework\Notifications\Channels\SmsChannel;
use Marwa\Framework\Notifications\NotificationManager;
use Marwa\Framework\Supports\Http as HttpSupport;
use Marwa\Framework\Tests\Fixtures\Notifications\DemoNotifiable;
use Marwa\Framework\Tests\Fixtures\Notifications\DemoNotification;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingBroadcastNotificationChannel;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingEventDispatcher;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingHttpClient;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingHttpNotificationChannel;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingKafkaPublisher;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingMailer;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingMailNotificationChannel;
use Marwa\Framework\Tests\Fixtures\Notifications\RecordingSmsNotificationChannel;
use PHPUnit\Framework\TestCase;

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

        $app->set(HttpSupport::class, $http);
        $app->set(MailerInterface::class, $mailer);
        $app->set(HttpClientInterface::class, $http);
        $app->set(KafkaPublisherInterface::class, $kafka);
        $app->set(EventDispatcherInterface::class, $events);
        $app->set(MailChannel::class, new RecordingMailNotificationChannel($mailer));
        $app->set(HttpChannel::class, new RecordingHttpNotificationChannel($http));
        $app->set(SmsChannel::class, new RecordingSmsNotificationChannel($http));
        $app->set(BroadcastChannel::class, new RecordingBroadcastNotificationChannel($events));

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
        $app->make(AppBootstrapper::class)->bootstrap();

        self::assertInstanceOf(NotificationManager::class, notification());
        self::assertSame($app->notifications(), notification());
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
}
