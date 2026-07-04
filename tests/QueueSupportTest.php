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

        $this->removeDirectory($this->basePath);

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

    public function testFileQueueRejectsTraversalQueueNames(): void
    {
        $app = new Application($this->basePath);
        $queue = $app->queue();

        $this->expectException(\InvalidArgumentException::class);
        $queue->push('mail:send', [], '../../outside');
    }

    public function testFileQueueRejectsTraversalQueueNamesWhenPopping(): void
    {
        $app = new Application($this->basePath);
        $queue = $app->queue();

        $this->expectException(\InvalidArgumentException::class);
        $queue->pop('../escape');
    }

    public function testFileQueueAllowsCommonQueueNameCharacters(): void
    {
        $app = new Application($this->basePath);
        $queue = $app->queue();

        $job = $queue->push('mail:send', [], 'emails.high-priority_1');

        self::assertSame('emails.high-priority_1', $job->queue());
        self::assertSame(1, $queue->size('emails.high-priority_1'));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
