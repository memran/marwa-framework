<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Schema\{Blueprint, Builder};
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\{AppBootstrapper, DatabaseBootstrapper};
use Marwa\Framework\Scheduling\Stores\DatabaseScheduleStore;
use Marwa\Framework\Scheduling\Task;
use PHPUnit\Framework\TestCase;

final class DatabaseScheduleOwnershipTest extends TestCase
{
    private string $path;
    private Application $app;

    protected function setUp(): void
    {
        unset($GLOBALS['marwa_app']);
        $this->path = sys_get_temp_dir() . '/marwa-scheduler-owner-' . bin2hex(random_bytes(6));
        mkdir($this->path . '/config', 0777, true);
        file_put_contents($this->path . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents($this->path . '/config/database.php', '<?php return ' . var_export([
            'enabled' => true, 'default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => $this->path . '/scheduler.sqlite']],
        ], true) . ';');
        $this->app = new Application($this->path);
        $this->app->make(AppBootstrapper::class)->bootstrap();
        $schema = new Builder($this->app->make(ConnectionManager::class));
        $schema->create('schedule_jobs', static function (Blueprint $table): void {
            $table->string('name', 191)->primary();
            $table->string('description')->nullable();
            $table->string('status')->default('idle');
            $table->text('last_message')->nullable();
            foreach (['last_ran_at', 'last_finished_at', 'last_failed_at', 'last_skipped_at'] as $column) {
                $table->dateTime($column)->nullable();
            }
            $table->timestamps();
        });
        $schema->create('schedule_jobs_locks', static function (Blueprint $table): void {
            $table->string('name', 191)->primary();
            $table->string('token', 32);
            $table->integer('last_dispatched_at')->nullable();
            $table->dateTime('expires_at');
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->path);
        unset($GLOBALS['marwa_app']);
    }

    private function store(): DatabaseScheduleStore
    {
        return new DatabaseScheduleStore($this->app->make(DatabaseBootstrapper::class), 'sqlite', 'schedule_jobs');
    }

    public function testSkippedAndStaleOwnersCannotClearFreshReservation(): void
    {
        $first = $this->store();
        $second = $this->store();
        $task = new Task('same-task', static fn (): null => null);
        $now = new \DateTimeImmutable();
        $old = $first->acquireLock($task, $now, 60);
        self::assertIsString($old);
        self::assertNull($second->acquireLock($task, $now, 60));
        $second->record($task, $now, 'skipped');
        self::assertNull($second->acquireLock($task, $now, 60));
        $later = $now->modify('+61 seconds');
        $fresh = $second->acquireLock($task, $later, 60);
        self::assertIsString($fresh);
        $first->record($task, $later, 'success');
        $first->releaseLock($task, $old);
        self::assertTrue($second->adoptLock($task, $fresh, $later));
        self::assertNull($first->acquireLock($task, $later, 60));
    }

    public function testCompletedOccurrenceRemainsDeduplicatedAfterRelease(): void
    {
        $store = $this->store();
        $task = new Task('occurrence', static fn (): null => null);
        $now = new \DateTimeImmutable();
        $token = $store->acquireLock($task, $now, 60, 100);
        self::assertIsString($token);
        $store->releaseLock($task, $token);
        self::assertNull($store->acquireLock($task, $now, 60, 100));
        self::assertNotNull($store->acquireLock($task, $now, 60, 101));
    }
}
