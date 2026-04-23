<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class FileQueue implements QueueInterface
{
    /**
     * @var array{enabled:bool,default:string,path:string,retryAfter:int}|null
     */
    private ?array $queueConfig = null;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        $config = $this->configuration();

        if (!$config['enabled']) {
            throw new \RuntimeException('Queue support is disabled.');
        }

        $queueName = $queue !== null && $queue !== '' ? $queue : $config['default'];
        $job = new QueuedJob(
            id: bin2hex(random_bytes(16)),
            name: $name,
            queue: $queueName,
            payload: $payload,
            attempts: 0,
            availableAt: time() + max(0, $delaySeconds),
            createdAt: time()
        );

        $this->ensureQueueDirectories($queueName);
        $path = $this->pendingPath($job);
        $contents = json_encode($job->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Unable to queue job [%s].', $job->id()));
        }

        return $job;
    }

    public function pop(?string $queue = null, ?\DateTimeImmutable $now = null): ?QueuedJob
    {
        $config = $this->configuration();

        if (!$config['enabled']) {
            return null;
        }

        $queueName = $queue !== null && $queue !== '' ? $queue : $config['default'];
        $this->ensureQueueDirectories($queueName);
        $timestamp = ($now ?? new \DateTimeImmutable())->getTimestamp();

        foreach ($this->pendingFiles($queueName) as $file) {
            $handle = fopen($file, 'c+');

            if ($handle === false || !flock($handle, LOCK_EX | LOCK_NB)) {
                if ($handle !== false) {
                    fclose($handle);
                }
                continue;
            }

            try {
                $job = $this->readJob($file);

                if ($job === null || $job->availableAt() > $timestamp) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    continue;
                }

                $reserved = $job->withAttempts($job->attempts() + 1);
                $processingPath = $this->processingPath($reserved);

                if (!@rename($file, $processingPath)) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    continue;
                }

                $this->writeJob($processingPath, $reserved);

                flock($handle, LOCK_UN);
                fclose($handle);

                return $reserved;
            } catch (\Throwable $e) {
                flock($handle, LOCK_UN);
                fclose($handle);
                throw $e;
            }
        }

        return null;
    }

    public function delete(QueuedJob $job): bool
    {
        $path = $this->processingPath($job);

        return is_file($path) ? unlink($path) : false;
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $released = $job->withAvailableAt(time() + max(0, $delaySeconds));
        $processingPath = $this->processingPath($job);

        if (is_file($processingPath)) {
            @unlink($processingPath);
        }

        $this->writeJob($this->pendingPath($released), $released);

        return $released;
    }

    public function fail(QueuedJob $job, ?string $reason = null): bool
    {
        $path = $this->failedPath($job);
        $payload = $job->toArray();

        if ($reason !== null && $reason !== '') {
            $payload['failureReason'] = $reason;
        }

        $this->writePayload($path, $payload);
        $this->delete($job);

        return true;
    }

    public function size(?string $queue = null): int
    {
        $queueName = $queue !== null && $queue !== '' ? $queue : $this->configuration()['default'];

        return count($this->pendingFiles($queueName));
    }

    /**
     * @return array{enabled:bool,default:string,path:string,retryAfter:int}
     */
    public function configuration(): array
    {
        if ($this->queueConfig !== null) {
            return $this->queueConfig;
        }

        $this->config->loadIfExists(QueueConfig::KEY . '.php');
        $this->queueConfig = QueueConfig::merge($this->app, $this->config->getArray(QueueConfig::KEY, []));

        return $this->queueConfig;
    }

    /**
     * @return list<string>
     */
    private function pendingFiles(string $queue): array
    {
        $files = glob($this->queueDirectory($queue) . '/pending/*.json');

        if (!is_array($files)) {
            return [];
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private function queueDirectory(string $queue): string
    {
        return rtrim($this->configuration()['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $queue;
    }

    private function ensureQueueDirectories(string $queue): void
    {
        foreach (['', '/pending', '/processing', '/failed'] as $suffix) {
            $directory = $this->queueDirectory($queue) . $suffix;

            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create queue directory [%s].', $directory));
            }
        }
    }

    private function pendingPath(QueuedJob $job): string
    {
        return sprintf(
            '%s/pending/%010d-%s.json',
            $this->queueDirectory($job->queue()),
            $job->availableAt(),
            $job->id()
        );
    }

    private function processingPath(QueuedJob $job): string
    {
        return sprintf('%s/processing/%s.json', $this->queueDirectory($job->queue()), $job->id());
    }

    private function failedPath(QueuedJob $job): string
    {
        return sprintf('%s/failed/%s.json', $this->queueDirectory($job->queue()), $job->id());
    }

    private function writeJob(string $path, QueuedJob $job): void
    {
        $this->writePayload($path, $job->toArray());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writePayload(string $path, array $payload): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create queue directory [%s].', $directory));
        }

        $contents = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Unable to write queue payload to [%s].', $path));
        }
    }

    private function readJob(string $path): ?QueuedJob
    {
        $contents = file_get_contents($path);

        if (!is_string($contents) || $contents === '') {
            $this->logger->warning('Skipping unreadable queued job file.', [
                'path' => $path,
            ]);

            return null;
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return QueuedJob::fromArray($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function pushAt(string $name, int $timestamp, array $payload = [], ?string $queue = null): QueuedJob
    {
        return $this->push($name, $payload, $queue, max(0, $timestamp - time()));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function pushRecurring(string $name, array $schedule, array $payload = [], ?string $queue = null): QueuedJob
    {
        // For recurring jobs, we store the schedule in payload and let scheduler handle it
        $payload['_recurring'] = $schedule;
        return $this->push($name, $payload, $queue, 0);
    }


}
