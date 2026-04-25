<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\Process;

use Symfony\Component\Process\Process;

interface ProcessInterface
{
    public function run(array $options = []): ProcessResult;

    public function execute(string $command, array $options = []): ProcessResult;

    public function parallel(array $commands, array $options = []): ProcessResult;

    public function runInBackground(string $command, array $options = []): int|null;

    public function input(string $input): self;

    public function timeout(int $timeout): self;

    public function retry(int $maxAttempts, int $delayMs = 1000): self;

    public function cwd(string $cwd): self;

    public function env(array $env): self;

    public function onStart(callable $callback): self;

    public function onComplete(callable $callback): self;

    public function onError(callable $callback): self;

    public function toFile(string $path): self;

    public function toDb(string $table, array $metadata = []): self;

    public function toRedis(string $key, int $ttl = 86400): self;

    public function queue(?string $queue = null, int $delaySeconds = 0): \Marwa\Framework\Queue\QueuedJob;

    public function queueAt(int $timestamp, ?string $queue = null): \Marwa\Framework\Queue\QueuedJob;

    public function schedule(string $expression, ?string $queue = null): \Marwa\Framework\Queue\QueuedJob;

    public function configuration(): array;
}