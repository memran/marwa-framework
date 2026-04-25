<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Console\Commands\QueueWorkCommand;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Queue\MailJob;
use Marwa\Framework\Queue\QueuedJob;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class QueueWorkCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-queue-work-' . bin2hex(random_bytes(6));
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        RecordingQueueJob::$handled = 0;
        RecordingMailable::$built = 0;
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testOnceExitsImmediatelyWhenQueueIsEmpty(): void
    {
        $queue = new RecordingQueue();
        $tester = $this->runWorker($queue, ['--once' => true, '--sleep' => 0]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['default'], $queue->poppedQueues);
        self::assertStringContainsString('Processed 0 jobs', $tester->getDisplay());
    }

    public function testMaxJobsStopsPersistentWorkerAfterLimit(): void
    {
        $queue = new RecordingQueue([
            $this->job('job-1', RecordingQueueJob::class, attempts: 1),
            $this->job('job-2', RecordingQueueJob::class, attempts: 1),
        ]);

        $tester = $this->runWorker($queue, ['--max-jobs' => 1, '--sleep' => 0]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['job-1'], $queue->completed);
        self::assertSame([], $queue->released);
        self::assertSame([], $queue->failed);
        self::assertSame(1, RecordingQueueJob::$handled);
    }

    public function testSuccessfulJobIsCompleted(): void
    {
        $queue = new RecordingQueue([
            $this->job('job-1', RecordingQueueJob::class, attempts: 1),
        ]);

        $tester = $this->runWorker($queue, ['--once' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['job-1'], $queue->completed);
        self::assertStringContainsString('Job completed successfully', $tester->getDisplay());
    }

    public function testFailingJobBelowMaxTriesIsReleased(): void
    {
        $queue = new RecordingQueue([
            $this->job('job-1', FailingQueueJob::class, attempts: 1),
        ]);

        $tester = $this->runWorker($queue, ['--once' => true, '--tries' => 3]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $queue->completed);
        self::assertSame(['job-1' => 90], $queue->released);
        self::assertSame([], $queue->failed);
    }

    public function testFailingJobAtMaxTriesIsFailed(): void
    {
        $queue = new RecordingQueue([
            $this->job('job-1', FailingQueueJob::class, attempts: 3),
        ]);

        $tester = $this->runWorker($queue, ['--once' => true, '--tries' => 3]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $queue->completed);
        self::assertSame([], $queue->released);
        self::assertSame(['job-1' => 'Intentional queue failure.'], $queue->failed);
    }

    public function testMailJobNameIsHandledByMailJobAdapter(): void
    {
        $queue = new RecordingQueue([
            $this->job('job-1', MailJob::NAME, ['class' => RecordingMailable::class, 'data' => ['subject' => 'Welcome']], 1),
        ]);
        $app = $this->applicationWithQueue($queue);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturn(1);
        $app->container()->addShared(MailerInterface::class, $mailer, true);

        $tester = $this->runCommand($app, ['--once' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['job-1'], $queue->completed);
        self::assertSame(1, RecordingMailable::$built);
    }

    public function testDisabledQueueExitsWithoutPollingForever(): void
    {
        file_put_contents(
            $this->basePath . '/config/queue.php',
            "<?php\n\nreturn [\n    'enabled' => false,\n];\n"
        );

        $tester = $this->runCommand(new Application($this->basePath), []);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Queue support is disabled.', $tester->getDisplay());
        self::assertStringContainsString('Processed 0 jobs', $tester->getDisplay());
    }

    public function testCommandUsesConfiguredFileDriverAtStartup(): void
    {
        file_put_contents(
            $this->basePath . '/config/queue.php',
            "<?php\n\nreturn [\n    'driver' => 'file',\n    'path' => __DIR__ . '/../storage/queue',\n];\n"
        );

        $app = new Application($this->basePath);
        $app->queue()->push(RecordingQueueJob::class, ['source' => 'file']);

        $tester = $this->runCommand($app, ['--once' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(1, RecordingQueueJob::$handled);
        self::assertSame(0, $app->queue()->size());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runWorker(RecordingQueue $queue, array $options): CommandTester
    {
        return $this->runCommand($this->applicationWithQueue($queue), $options);
    }

    private function applicationWithQueue(RecordingQueue $queue): Application
    {
        $app = new Application($this->basePath);
        $app->container()->addShared(QueueInterface::class, $queue, true);

        return $app;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runCommand(Application $app, array $options): CommandTester
    {
        $command = new QueueWorkCommand();
        $command->setMarwaApplication($app);
        $tester = new CommandTester($command);
        $tester->execute($options);

        return $tester;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function job(string $id, string $name, array $payload = [], int $attempts = 0): QueuedJob
    {
        return new QueuedJob(
            id: $id,
            name: $name,
            queue: 'default',
            payload: $payload,
            attempts: $attempts,
            availableAt: time(),
            createdAt: time()
        );
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

            $target = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                $this->removeDirectory($target);
                continue;
            }

            unlink($target);
        }

        rmdir($path);
    }
}

final class RecordingQueue implements QueueInterface
{
    /**
     * @var list<QueuedJob>
     */
    private array $jobs;

    /**
     * @var list<string>
     */
    public array $poppedQueues = [];

    /**
     * @var list<string>
     */
    public array $completed = [];

    /**
     * @var array<string, int>
     */
    public array $released = [];

    /**
     * @var array<string, string|null>
     */
    public array $failed = [];

    /**
     * @param list<QueuedJob> $jobs
     */
    public function __construct(array $jobs = [])
    {
        $this->jobs = $jobs;
    }

    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        $job = QueuedJob::fromArray([
            'id' => 'job-' . (count($this->jobs) + 1),
            'name' => $name,
            'queue' => $queue ?? 'default',
            'payload' => $payload,
            'attempts' => 0,
            'availableAt' => time() + max(0, $delaySeconds),
            'createdAt' => time(),
        ]);
        $this->jobs[] = $job;

        return $job;
    }

    public function pushAt(string $name, int $timestamp, array $payload = [], ?string $queue = null): QueuedJob
    {
        return $this->push($name, $payload, $queue, max(0, $timestamp - time()));
    }

    public function pushRecurring(string $name, array $schedule, array $payload = [], ?string $queue = null): QueuedJob
    {
        $payload['_recurring'] = $schedule;

        return $this->push($name, $payload, $queue);
    }

    public function pop(?string $queue = null, ?\DateTimeImmutable $now = null): ?QueuedJob
    {
        $this->poppedQueues[] = $queue ?? 'default';

        return array_shift($this->jobs);
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $this->released[$job->id()] = $delaySeconds;

        return $job->withAvailableAt(time() + max(0, $delaySeconds));
    }

    public function complete(QueuedJob $job): void
    {
        $this->completed[] = $job->id();
    }

    public function fail(QueuedJob $job, ?string $error = null): void
    {
        $this->failed[$job->id()] = $error;
    }

    public function size(?string $queue = null): int
    {
        return count($this->jobs);
    }
}

final class RecordingQueueJob
{
    public static int $handled = 0;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload) {}

    public function handle(Application $app): string
    {
        self::$handled++;

        return (string) ($this->payload['result'] ?? 'ok');
    }
}

final class FailingQueueJob
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload)
    {
        if (isset($payload['message'])) {
            throw new \RuntimeException((string) $payload['message']);
        }
    }

    public function handle(Application $app): void
    {
        throw new \RuntimeException('Intentional queue failure.');
    }
}

final class RecordingMailable extends Mailable
{
    public static int $built = 0;

    public function build(MailerInterface $mailer): MailerInterface
    {
        self::$built++;

        return $mailer;
    }
}
