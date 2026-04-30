<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Queue\DatabaseQueue;
use Marwa\Framework\Queue\RedisQueue;
use PHPUnit\Framework\TestCase;

final class QueueSupportTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-queue-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/database', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }

        foreach ([
            $this->basePath . '/config/queue.php',
            $this->basePath . '/config/database.php',
            $this->basePath . '/.env',
            $this->basePath . '/database/database.sqlite',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath . '/database');
        @rmdir($this->basePath);

        unset($GLOBALS['marwa_app']);
    }

    public function testQueueManagerResolvesRedisDriverWhenConfigured(): void
    {
        file_put_contents(
            $this->basePath . '/config/queue.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'driver' => 'redis',
];
PHP
        );

        $app = new Application($this->basePath);
        $queue = $app->make(QueueInterface::class);

        self::assertInstanceOf(RedisQueue::class, $queue);
    }

    public function testDatabaseQueuePushAtClampsPastTimestampToNow(): void
    {
        file_put_contents(
            $this->basePath . '/config/database.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
            'debug' => false,
        ],
    ],
];
PHP
        );
        file_put_contents(
            $this->basePath . '/config/queue.php',
            <<<'PHP'
<?php

return [
    'enabled' => true,
    'driver' => 'database',
    'database' => [
        'connection' => 'sqlite',
        'table' => 'jobs',
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $app->make(AppBootstrapper::class)->bootstrap();
        $this->handlersBooted = true;

        /** @var ConnectionManager $manager */
        $manager = $app->make(ConnectionManager::class);
        $manager->getPdo()->exec(<<<'SQL'
CREATE TABLE jobs (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL,
    available_at INTEGER NOT NULL,
    reserved_at INTEGER NULL,
    reserved_by TEXT NULL,
    completed_at INTEGER NULL,
    failed_at INTEGER NULL,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
)
SQL);

        $queue = $app->queue();
        self::assertInstanceOf(DatabaseQueue::class, $queue);

        $now = time();
        $job = $queue->pushAt('mail:send', $now - 3600);

        self::assertGreaterThanOrEqual($now, $job->availableAt());
        self::assertLessThanOrEqual($now + 2, $job->availableAt());
        self::assertSame($job->availableAt(), (int) $manager->getPdo()->query('SELECT available_at FROM jobs LIMIT 1')->fetchColumn());
    }
}
