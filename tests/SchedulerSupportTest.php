<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Queue\FileQueue;
use Marwa\Framework\Scheduling\Scheduler;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreResolver;
use PHPUnit\Framework\TestCase;

/**
 * @group slow
 */
final class SchedulerSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-scheduler-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testFileQueueCanPushPopReleaseAndFailJobs(): void
    {
        $app = new Application($this->basePath);
        $queue = $app->queue();

        $job = $queue->push('emails:send', ['user_id' => 10], delaySeconds: 0);

        self::assertSame(1, $queue->size());

        $reserved = $queue->pop(now: new \DateTimeImmutable('@' . $job->availableAt()));

        self::assertNotNull($reserved);
        self::assertSame('emails:send', $reserved->name());
        self::assertSame(['user_id' => 10], $reserved->payload());
        self::assertSame(1, $reserved->attempts());

        $released = $queue->release($reserved, 2);
        self::assertSame(1, $queue->size());
        self::assertNull($queue->pop(now: new \DateTimeImmutable()));

        $reservedAgain = $queue->pop(now: new \DateTimeImmutable('@' . ($released->availableAt() + 1)));

        self::assertNotNull($reservedAgain);
        self::assertTrue($queue->fail($reservedAgain, 'test failure'));
        self::assertFileExists($this->basePath . '/storage/queue/default/failed/' . $reservedAgain->id() . '.json');
    }

    public function testSchedulerRunsDueCallbacksAndQueuedJobs(): void
    {
        $app = new Application($this->basePath);
        /** @var Scheduler $scheduler */
        $scheduler = $app->schedule();
        /** @var FileQueue $queue */
        $queue = $app->queue();

        $scheduler->call(function (Application $application, \DateTimeImmutable $time): void {
            file_put_contents(
                $application->basePath('schedule.log'),
                $time->format('Y-m-d H:i:s') . PHP_EOL,
                FILE_APPEND
            );
        }, 'write-log')->everySecond();

        $scheduler->queue('digest:send', ['batch' => 1], name: 'queue-digest')->everySeconds(5);

        $summary = $scheduler->runDue(new \DateTimeImmutable('@1700000010'));

        self::assertSame(['write-log', 'queue-digest'], $summary['ran']);
        self::assertFileExists($this->basePath . '/schedule.log');
        self::assertSame(1, $queue->size());

        $summary = $scheduler->runDue(new \DateTimeImmutable('@1700000011'));

        self::assertSame(['write-log'], $summary['ran']);
    }

    public function testFileSchedulerStorePersistsTaskState(): void
    {
        $app = new Application($this->basePath);
        $scheduler = $app->schedule();

        $scheduler->call(static function (): void {}, 'file-store-task')->everySecond();

        $scheduler->runDue(new \DateTimeImmutable('@1700000010'));

        $statePath = $this->basePath . '/storage/framework/schedule/state/file-store-task.json';
        self::assertFileExists($statePath);

        $state = json_decode((string) file_get_contents($statePath), true);

        self::assertIsArray($state);
        self::assertSame('success', $state['status']);
        self::assertSame('file-store-task', $state['name']);
    }

    public function testDatabaseSchedulerStoreCanSkipOverlappingTasksAndPersistState(): void
    {
        file_put_contents(
            $this->basePath . '/config/database.php',
            sprintf(
                <<<'PHP'
<?php

return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => '%s',
        ],
    ],
];
PHP,
                $this->basePath . '/database.sqlite'
            )
        );

        file_put_contents(
            $this->basePath . '/config/schedule.php',
            <<<'PHP'
<?php

return [
    'driver' => 'database',
    'database' => [
        'connection' => 'sqlite',
        'table' => 'schedule_jobs',
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $database = $app->make(DatabaseBootstrapper::class)->bootstrap();

        self::assertNotNull($database);

        $database->getPdo('sqlite')->exec(
            <<<'SQL'
CREATE TABLE schedule_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(191) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'idle',
    last_message TEXT NULL,
    lock_expires_at DATETIME NULL,
    last_ran_at DATETIME NULL,
    last_finished_at DATETIME NULL,
    last_failed_at DATETIME NULL,
    last_skipped_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
)
SQL
        );

        $scheduler = $app->schedule();
        $task = $scheduler->call(static function (): void {}, 'database-store-task')->everySecond()->withoutOverlapping();

        $store = $app->make(ScheduleStoreResolver::class)->resolve($scheduler->configuration());
        $lock = $store->acquireLock($task, new \DateTimeImmutable('@1700000010'), 60);

        self::assertNotNull($lock);

        $summary = $scheduler->runDue(new \DateTimeImmutable('@1700000010'));

        self::assertSame(['database-store-task'], $summary['skipped']);

        $row = $database->getPdo('sqlite')->query('SELECT status, last_message FROM schedule_jobs WHERE name = \'database-store-task\'')
            ?->fetch(\PDO::FETCH_ASSOC);

        self::assertIsArray($row);
        self::assertSame('skipped', $row['status']);
        self::assertSame('Skipped because it is already running.', $row['last_message']);

        $store->releaseLock($task, $lock);
    }

    public function testCacheSchedulerStoreCanSkipOverlappingTasksAndPersistState(): void
    {
        file_put_contents(
            $this->basePath . '/config/cache.php',
            <<<'PHP'
<?php

return [
    'driver' => 'memory',
    'namespace' => 'scheduler-tests',
    'buffered' => false,
    'transactional' => false,
    'stampede' => [
        'enabled' => false,
        'sla' => 250,
    ],
];
PHP
        );

        file_put_contents(
            $this->basePath . '/config/schedule.php',
            <<<'PHP'
<?php

return [
    'driver' => 'cache',
    'cache' => [
        'namespace' => 'scheduler-runtime',
    ],
];
PHP
        );

        $app = new Application($this->basePath);
        $scheduler = $app->schedule();
        $task = $scheduler->call(static function (): void {}, 'cache-store-task')->everySecond()->withoutOverlapping();
        $store = $app->make(ScheduleStoreResolver::class)->resolve($scheduler->configuration());

        $lock = $store->acquireLock($task, new \DateTimeImmutable('@1700000010'), 60);

        self::assertNotNull($lock);

        $summary = $scheduler->runDue(new \DateTimeImmutable('@1700000010'));

        self::assertSame(['cache-store-task'], $summary['skipped']);

        $state = $app->cache()->get('scheduler-runtime.state.cache-store-task');

        self::assertIsArray($state);
        self::assertSame('skipped', $state['status']);
        self::assertSame('Skipped because it is already running.', $state['last_message']);

        $store->releaseLock($task, $lock);
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
