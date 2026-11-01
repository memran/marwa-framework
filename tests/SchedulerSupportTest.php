<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Contracts\ScheduleStoreResolverInterface;
use Marwa\Framework\Queue\QueuedJob;
use Marwa\Framework\Scheduling\Scheduler;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreInterface;
use Marwa\Framework\Scheduling\Task;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SchedulerSupportTest extends TestCase
{
    private string $basePath;
    private bool $handlersBooted = false;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-scheduler-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        if ($this->handlersBooted) {
            restore_error_handler();
            restore_exception_handler();
        }
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testSchedulerRunsCallbacksAndQueuesJobsWithMockedDependencies(): void
    {
        $app = new Application($this->basePath);
        $ran = [];
        $queuedJob = new QueuedJob(
            id: 'job-1',
            name: 'digest:send',
            queue: 'default',
            payload: ['batch' => 1],
            attempts: 0,
            availableAt: 1700000010,
            createdAt: 1700000010
        );

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects(self::once())
            ->method('push')
            ->with('digest:send', ['batch' => 1], null, 0)
            ->willReturn($queuedJob);

        $store = $this->createMock(ScheduleStoreInterface::class);
        $store->expects(self::exactly(2))
            ->method('record');
        $store->expects(self::exactly(2))
            ->method('releaseLock')
            ->with($this->isInstanceOf(\Marwa\Framework\Scheduling\Task::class), null);
        $store->expects(self::never())
            ->method('acquireLock');

        $resolver = $this->createMock(ScheduleStoreResolverInterface::class);
        $resolver->expects(self::once())
            ->method('resolve')
            ->willReturn($store);

        $scheduler = new Scheduler($app, new NullLogger(), $queue, $resolver);

        $scheduler->call(function (Application $application, \DateTimeImmutable $time) use (&$ran): void {
            $ran[] = $application->basePath() . '|' . $time->format('Y-m-d H:i:s');
        }, 'write-log')->everySecond();
        $scheduler->queue('digest:send', ['batch' => 1], name: 'queue-digest')->everySecond();

        $summary = $scheduler->runDue(new \DateTimeImmutable('@1700000010'));

        self::assertSame(['write-log', 'queue-digest'], $summary['ran']);
        self::assertSame(1, count($ran));
        self::assertSame($this->basePath, explode('|', $ran[0])[0]);
    }

    public function testSchedulerSkipsLockedTasksWithMockedStore(): void
    {
        $app = new Application($this->basePath);

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects(self::never())->method('push');

        $store = $this->createMock(ScheduleStoreInterface::class);
        $store->expects(self::once())
            ->method('acquireLock')
            ->willReturn(null);
        $store->expects(self::once())
            ->method('record')
            ->with(
                $this->isInstanceOf(\Marwa\Framework\Scheduling\Task::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                'skipped',
                'Skipped because it is already running.'
            );
        $store->expects(self::never())
            ->method('releaseLock');

        $resolver = $this->createMock(ScheduleStoreResolverInterface::class);
        $resolver->expects(self::once())
            ->method('resolve')
            ->willReturn($store);

        $scheduler = new Scheduler($app, new NullLogger(), $queue, $resolver);
        $scheduler->call(static function (): void {}, 'locked-task')->everySecond()->withoutOverlapping();

        $summary = $scheduler->runDue(new \DateTimeImmutable('@1700000010'));

        self::assertSame([], $summary['ran']);
        self::assertSame(['locked-task'], $summary['skipped']);
        self::assertSame([], $summary['failed']);
    }

    public function testSchedulerResolvesStoreFromCurrentConfigurationOnEachRun(): void
    {
        $app = new Application($this->basePath);
        /** @var \Marwa\Framework\Supports\Config $config */
        $config = $app->make(\Marwa\Framework\Supports\Config::class);
        $config->set('schedule.driver', 'file');

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects(self::never())->method('push');

        $resolver = new RecordingScheduleStoreResolver();
        $scheduler = new Scheduler($app, new NullLogger(), $queue, $resolver);
        $scheduler->call(static function (): void {}, 'dynamic-driver')->everySecond();

        $scheduler->runDue(new \DateTimeImmutable('@1700000010'));
        $config->set('schedule.driver', 'cache');
        $scheduler->runDue(new \DateTimeImmutable('@1700000011'));

        self::assertSame(['file', 'cache'], $resolver->drivers);
    }

    public function testEveryMinuteTasksAlignToWallClockBoundaries(): void
    {
        $app = new Application($this->basePath);
        $createdAt = new \DateTimeImmutable('@1700000001');
        $task = new Task('wall-clock-minute', static function (): void {}, $createdAt);

        $task->everyMinute();

        self::assertSame($createdAt, $task->createdAt());
        self::assertFalse($task->isDue($app, new \DateTimeImmutable('@1700000039')));
        self::assertTrue($task->isDue($app, new \DateTimeImmutable('@1700000040')));
    }

    public function testNonzeroCommandExitIsRecordedAsFailure(): void
    {
        $app = new Application($this->basePath);
        $command = new \Symfony\Component\Console\Command\Command('test:failed-command');
        $command->setCode(static fn (): int => 2);
        $this->handlersBooted = true;
        $app->console()->application()->add($command);
        $store = $this->createMock(ScheduleStoreInterface::class);
        $store->expects(self::once())->method('record')->with(
            self::isInstanceOf(Task::class),
            self::isInstanceOf(\DateTimeImmutable::class),
            'failed',
            self::stringContains('exited with code 2')
        );
        $resolver = $this->createMock(ScheduleStoreResolverInterface::class);
        $resolver->method('resolve')->willReturn($store);
        $scheduler = new Scheduler($app, new NullLogger(), $this->createMock(QueueInterface::class), $resolver);
        $scheduler->command('test:failed-command', [], 'failed-command')->everySecond();
        self::assertSame(['failed-command'], $scheduler->runDue()['failed']);
    }

    public function testBackgroundDispatchDoesNotFallBackToInlineExecution(): void
    {
        $app = new Application($this->basePath);
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects(self::never())->method('push');
        $resolver = $this->createMock(ScheduleStoreResolverInterface::class);
        $resolver->method('resolve')->willThrowException(new \RuntimeException('Storage unavailable'));
        $scheduler = new Scheduler($app, new NullLogger(), $queue, $resolver);
        $ran = 0;
        $scheduler->call(static function () use (&$ran): void {
            $ran++;
        }, 'background')->everySecond()->inBackground();
        $summary = $scheduler->runDue();
        self::assertSame(['background'], $summary['failed']);
        self::assertSame(0, $ran);
    }

    public function testSchedulerCommandReportsDispatchFailureWithNonzeroExit(): void
    {
        $app = new Application($this->basePath);
        $resolver = $this->createMock(ScheduleStoreResolverInterface::class);
        $resolver->method('resolve')->willThrowException(new \RuntimeException('Store unavailable'));
        $scheduler = new Scheduler($app, new NullLogger(), $this->createMock(QueueInterface::class), $resolver);
        $scheduler->call(static fn (): null => null, 'background')->everySecond()->inBackground();
        $app->container()->addShared(Scheduler::class, $scheduler, true);
        $command = (new \Marwa\Framework\Console\Commands\ScheduleRunCommand())->setMarwaApplication($app);
        $tester = new \Symfony\Component\Console\Tester\CommandTester($command);
        $tester->execute(['--once' => true]);
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('failed', $tester->getDisplay());
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
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

final class RecordingScheduleStoreResolver implements ScheduleStoreResolverInterface
{
    /**
     * @var list<string>
     */
    public array $drivers = [];

    /**
     * @param array{
     *     driver:string,
     *     file:array{path:string},
     *     cache:array{namespace:string},
     *     database:array{connection:string,table:string}
     * } $config
     */
    public function resolve(array $config): ScheduleStoreInterface
    {
        $this->drivers[] = $config['driver'];

        return new NullScheduleStore();
    }
}

final class NullScheduleStore implements ScheduleStoreInterface
{
    public function acquireLock(\Marwa\Framework\Scheduling\Task $task, \DateTimeImmutable $time, int $ttlSeconds): mixed
    {
        return $task->name();
    }

    public function releaseLock(\Marwa\Framework\Scheduling\Task $task, mixed $lock): void {}

    public function record(\Marwa\Framework\Scheduling\Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void {}
}
