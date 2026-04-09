<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\Framework\Scheduling\Task;

final class FileScheduleStore implements ScheduleStoreInterface
{
    public function __construct(private string $path) {}

    public function acquireLock(Task $task, \DateTimeImmutable $time, int $ttlSeconds): mixed
    {
        $directory = $this->lockDirectory();

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create scheduler lock directory [%s].', $directory));
        }

        $path = $directory . DIRECTORY_SEPARATOR . $this->normalizeName($task->name()) . '.lock';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Unable to create scheduler lock file [%s].', $path));
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        fwrite($handle, json_encode([
            'task' => $task->name(),
            'expires_at' => $time->modify(sprintf('+%d seconds', $ttlSeconds))->format(DATE_ATOM),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
        ftruncate($handle, ftell($handle) ?: 0);

        return $handle;
    }

    public function releaseLock(Task $task, mixed $lock): void
    {
        if (!is_resource($lock)) {
            return;
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    public function record(Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void
    {
        $directory = $this->stateDirectory();

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create scheduler state directory [%s].', $directory));
        }

        $path = $directory . DIRECTORY_SEPARATOR . $this->normalizeName($task->name()) . '.json';
        $state = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];

        if (!is_array($state)) {
            $state = [];
        }

        $state['name'] = $task->name();
        $state['description'] = $task->description();
        $state['status'] = $status;
        $state['last_message'] = $message;
        $state['updated_at'] = $time->format(DATE_ATOM);

        if ($status === 'success') {
            $state['last_ran_at'] = $time->format(DATE_ATOM);
            $state['last_finished_at'] = $time->format(DATE_ATOM);
        }

        if ($status === 'failed') {
            $state['last_failed_at'] = $time->format(DATE_ATOM);
        }

        if ($status === 'skipped') {
            $state['last_skipped_at'] = $time->format(DATE_ATOM);
        }

        file_put_contents(
            $path,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }

    private function lockDirectory(): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'locks';
    }

    private function stateDirectory(): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'state';
    }

    private function normalizeName(string $name): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_\-]+/', '-', strtolower($name)) ?: 'task';

        return trim($normalized, '-');
    }
}
