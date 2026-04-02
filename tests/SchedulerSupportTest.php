<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Queue\FileQueue;
use Marwa\Framework\Scheduling\Scheduler;
use PHPUnit\Framework\TestCase;

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
