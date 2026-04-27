<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\Process;

interface ProcessInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function run(array $options = []): ProcessResult;

    /**
     * @param array<string, mixed> $options
     */
    public function execute(string $command, array $options = []): ProcessResult;

    /**
     * @param list<string> $commands
     * @param array<string, mixed> $options
     */
    public function parallel(array $commands, array $options = []): ProcessResult;

    /**
     * @param array<string, mixed> $options
     */
    public function runInBackground(string $command, array $options = []): int|null;

    public function input(string $input): self;

    public function timeout(int $timeout): self;

    public function retry(int $maxAttempts, int $delayMs = 1000): self;

    public function cwd(string $cwd): self;

    /**
     * @param array<string, string> $env
     */
    public function env(array $env): self;

    public function onStart(callable $callback): self;

    public function onComplete(callable $callback): self;

    public function onError(callable $callback): self;

    public function toFile(string $path): self;

    /**
     * @param array<string, mixed> $metadata
     */
    public function toDb(string $table, array $metadata = []): self;

    public function toRedis(string $key, int $ttl = 86400): self;

    public function queue(?string $queue = null, int $delaySeconds = 0): \Marwa\Framework\Queue\QueuedJob;

    public function queueAt(int $timestamp, ?string $queue = null): \Marwa\Framework\Queue\QueuedJob;

    public function schedule(string $expression, ?string $queue = null): \Marwa\Framework\Queue\QueuedJob;

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array;
}
