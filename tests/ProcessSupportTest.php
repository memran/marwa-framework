<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Process\ProcessAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\Process\ProcessResult;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Process\ProcessJob;
use Marwa\Framework\Queue\QueuedJob;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ProcessSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-process-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        unset($GLOBALS['marwa_app']);
    }

    public function testProcessResultBuildsValidDateTimesFromSymfonyProcess(): void
    {
        $script = $this->basePath . '/echo.php';
        file_put_contents(
            $script,
            <<<'PHP'
<?php
echo 'ok';
PHP
        );

        $process = new Process([PHP_BINARY, $script]);
        $process->run();

        $result = ProcessResult::fromSymfonyProcess($process, microtime(true) - 0.01);

        self::assertInstanceOf(\DateTimeInterface::class, $result->getStartTime());
        self::assertInstanceOf(\DateTimeInterface::class, $result->getEndTime());
        self::assertTrue($result->isSuccessful());
        self::assertSame('ok', $result->getOutput());
    }

    public function testProcessAdapterQueuesSerializableStateAndQueuedJobRestoresOutputHandler(): void
    {
        $script = $this->basePath . '/queued-process.php';
        file_put_contents(
            $script,
            <<<'PHP'
<?php
echo 'queued';
PHP
        );

        $outputPath = $this->basePath . '/process-output.log';
        $queue = new RecordingProcessQueue();

        $app = new Application($this->basePath);
        $app->container()->addShared(QueueInterface::class, $queue, true);

        /** @var ProcessAdapter $adapter */
        $adapter = $app->make(ProcessAdapter::class);
        $startedAt = time();
        $adapter
            ->command(sprintf('"%s" "%s"', PHP_BINARY, $script))
            ->input('stdin')
            ->retry(2, 250)
            ->toFile($outputPath)
            ->queue('process-jobs', 15);

        self::assertSame('process', $queue->lastJob->name());
        self::assertSame('process-jobs', $queue->lastJob->queue());
        self::assertGreaterThanOrEqual($startedAt + 15, $queue->lastJob->availableAt());
        self::assertLessThanOrEqual($startedAt + 16, $queue->lastJob->availableAt());
        self::assertSame(sprintf('"%s" "%s"', PHP_BINARY, $script), $queue->payload['command'] ?? null);
        self::assertSame('stdin', $queue->payload['input'] ?? null);
        self::assertSame(2, $queue->payload['options']['retry'] ?? null);
        self::assertSame(250, $queue->payload['options']['retry_delay_ms'] ?? null);
        self::assertSame('file', $queue->payload['output_handler']['type'] ?? null);
        self::assertSame($outputPath, $queue->payload['output_handler']['config']['path'] ?? null);

        $job = new ProcessJob($queue->payload);
        $job->handle($app);

        self::assertFileExists($outputPath);
        self::assertStringContainsString('queued', (string) file_get_contents($outputPath));
    }

    public function testParallelProcessUsesConfiguredWorkingDirectoryAndEnvironment(): void
    {
        $script = $this->basePath . '/parallel-process.php';
        file_put_contents(
            $script,
            <<<'PHP'
<?php
echo getenv('MARWA_PROCESS_TEST') . '|' . getcwd();
PHP
        );

        $app = new Application($this->basePath);
        /** @var ProcessAdapter $adapter */
        $adapter = $app->make(ProcessAdapter::class);

        $result = $adapter->parallel(
            [sprintf('"%s" "%s"', PHP_BINARY, $script)],
            [
                'cwd' => $this->basePath,
                'env' => ['MARWA_PROCESS_TEST' => 'ok'],
            ]
        );

        $outputs = json_decode($result->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $result->getExitCode());
        self::assertSame(['ok|' . str_replace('/', DIRECTORY_SEPARATOR, $this->basePath)], $outputs);
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

            @unlink($target);
        }

        @rmdir($path);
    }
}

final class RecordingProcessQueue implements QueueInterface
{
    public ?QueuedJob $lastJob = null;
    /**
     * @var array<string, mixed>
     */
    public array $payload = [];

    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        $job = QueuedJob::fromArray([
            'id' => 'process-job',
            'name' => $name,
            'queue' => $queue ?? 'default',
            'payload' => $payload,
            'attempts' => 0,
            'availableAt' => time() + max(0, $delaySeconds),
            'createdAt' => time(),
        ]);

        $this->lastJob = $job;
        $this->payload = $payload;

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
        return null;
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        return $job->withAvailableAt(time() + max(0, $delaySeconds));
    }

    public function complete(QueuedJob $job): void {}

    public function fail(QueuedJob $job, ?string $error = null): void {}

    public function size(?string $queue = null): int
    {
        return 0;
    }
}
