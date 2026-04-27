<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Process;

use Closure;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\Process\ProcessInterface;
use Marwa\Framework\Contracts\Process\ProcessResult;
use Marwa\Framework\Supports\Config;
use Symfony\Component\Process\Process;

final class ProcessAdapter implements ProcessInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    private ?ProcessOutputHandler $outputHandler = null;
    private ?string $input = null;
    private int $retryAttempts = 0;
    private int $retryDelayMs = 1000;
    private ?Closure $onStartCallback = null;
    private ?Closure $onCompleteCallback = null;
    private ?Closure $onErrorCallback = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(string $command, array $options = []): ProcessResult
    {
        return $this->execute($command, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function run(array $options = []): ProcessResult
    {
        $command = (string) ($this->options['command'] ?? '');

        return $this->execute($command, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function execute(string $command, array $options = []): ProcessResult
    {
        $options = array_merge($this->options, $options);
        $timeout = $options['timeout'] ?? $this->configuration()['timeout'];
        $cwd = $options['cwd'] ?? $this->configuration()['cwd'];
        $env = is_array($options['env'] ?? null) ? $options['env'] : [];
        $maxAttempts = (int) ($options['retry'] ?? $this->retryAttempts);
        $attempt = 0;
        $result = null;

        do {
            $startTime = microtime(true);

            if ($this->onStartCallback instanceof Closure) {
                ($this->onStartCallback)($command);
            }

            try {
                $process = Process::fromShellCommandLine($command, $cwd, $env, null, $timeout);

                if ($this->input !== null) {
                    $process->setInput($this->input);
                }

                $process->run();

                $result = ProcessResult::fromSymfonyProcess($process, $startTime);

                if ($this->onCompleteCallback instanceof Closure) {
                    ($this->onCompleteCallback)($result);
                }

                if ($this->outputHandler instanceof ProcessOutputHandler) {
                    $this->outputHandler->write($result);
                }

                if ($result->isSuccessful() || $attempt >= $maxAttempts) {
                    return $result;
                }
            } catch (\Throwable $e) {
                $result = ProcessResult::error($command, $e->getMessage(), microtime(true) - $startTime);

                if ($this->onErrorCallback instanceof Closure) {
                    ($this->onErrorCallback)($e);
                }

                if ($this->outputHandler instanceof ProcessOutputHandler) {
                    $this->outputHandler->write($result);
                }

                if ($attempt >= $maxAttempts) {
                    return $result;
                }
            }

            if ($attempt < $maxAttempts) {
                usleep($this->retryDelayMs * 1000);
            }

            $attempt++;
        } while ($attempt <= $maxAttempts);

        return $result;
    }

    /**
     * @param list<string> $commands
     * @param array<string, mixed> $options
     */
    public function parallel(array $commands, array $options = []): ProcessResult
    {
        if ($commands === []) {
            return ProcessResult::error('parallel', 'No commands provided');
        }

        $startTime = microtime(true);
        $processes = [];
        $finished = [];

        foreach ($commands as $index => $cmd) {
            $processes[$index] = Process::fromShellCommandLine($cmd);
        }

        foreach ($processes as $process) {
            $process->start();
        }

        while ($processes !== []) {
            foreach ($processes as $index => $process) {
                if (!$process->isRunning()) {
                    $finished[$index] = $process;
                    unset($processes[$index]);
                }
            }

            if ($processes !== []) {
                usleep(100000);
            }
        }

        $outputs = [];
        $exitCodes = [];

        foreach ($finished as $i => $process) {
            $outputs[$i] = $process->getOutput();
            $exitCodes[$i] = $process->getExitCode() ?? 0;
        }

        $result = new ProcessResult(
            'parallel: ' . implode(' && ', $commands),
            array_sum($exitCodes) === 0 ? 0 : -1,
            json_encode($outputs, JSON_THROW_ON_ERROR),
            '',
            microtime(true) - $startTime,
            memory_get_peak_usage(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        if ($this->outputHandler instanceof ProcessOutputHandler) {
            $this->outputHandler->write($result);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function runInBackground(string $command, array $options = []): ?int
    {
        $cwd = $options['cwd'] ?? $this->configuration()['cwd'];
        $process = Process::fromShellCommandLine($command, $cwd);
        $process->start();

        return $process->getPid();
    }

    public function input(string $input): self
    {
        $this->input = $input;

        return $this;
    }

    public function timeout(int $timeout): self
    {
        $this->options['timeout'] = $timeout;

        return $this;
    }

    public function retry(int $maxAttempts, int $delayMs = 1000): self
    {
        $this->retryAttempts = $maxAttempts;
        $this->retryDelayMs = $delayMs;

        return $this;
    }

    public function cwd(string $cwd): self
    {
        $this->options['cwd'] = $cwd;

        return $this;
    }

    /**
     * @param array<string, string> $env
     */
    public function env(array $env): self
    {
        $this->options['env'] = $env;

        return $this;
    }

    public function onStart(callable $callback): self
    {
        $this->onStartCallback = Closure::fromCallable($callback);

        return $this;
    }

    public function onComplete(callable $callback): self
    {
        $this->onCompleteCallback = Closure::fromCallable($callback);

        return $this;
    }

    public function onError(callable $callback): self
    {
        $this->onErrorCallback = Closure::fromCallable($callback);

        return $this;
    }

    public function toFile(string $path): self
    {
        $this->outputHandler = new FileOutputHandler($path);

        return $this;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function toDb(string $table, array $metadata = []): self
    {
        $this->outputHandler = new DatabaseOutputHandler($table, $metadata);

        return $this;
    }

    public function toRedis(string $key, int $ttl = 86400): self
    {
        $this->outputHandler = new RedisOutputHandler($key, $ttl);

        return $this;
    }

    public function queue(?string $queue = null, int $delaySeconds = 0): \Marwa\Framework\Queue\QueuedJob
    {
        return $this->app->queue()->push(
            \Marwa\Framework\Process\ProcessJob::NAME,
            $this->buildPayload(),
            $queue,
            $delaySeconds
        );
    }

    public function queueAt(int $timestamp, ?string $queue = null): \Marwa\Framework\Queue\QueuedJob
    {
        return $this->app->queue()->pushAt(
            \Marwa\Framework\Process\ProcessJob::NAME,
            $timestamp,
            $this->buildPayload(),
            $queue
        );
    }

    public function schedule(string $expression, ?string $queue = null): \Marwa\Framework\Queue\QueuedJob
    {
        return $this->app->queue()->pushRecurring(
            \Marwa\Framework\Process\ProcessJob::NAME,
            ['expression' => $expression],
            $this->buildPayload(),
            $queue
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        $configData = $this->config->getArray('process', []);

        return array_merge([
            'timeout' => 300,
            'cwd' => $this->app->basePath(),
            'env' => [],
            'retry' => [
                'max_attempts' => 3,
                'delay' => 1000,
            ],
            'handlers' => [
                'file' => ['path' => 'storage/logs/process'],
                'db' => ['table' => 'process_logs'],
                'redis' => ['key_prefix' => 'process:'],
            ],
        ], $configData);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        return [
            'command' => $this->options['command'] ?? '',
            'options' => [
                'timeout' => $this->options['timeout'] ?? null,
                'cwd' => $this->options['cwd'] ?? null,
                'env' => is_array($this->options['env'] ?? null) ? $this->options['env'] : [],
                'retry' => $this->retryAttempts,
            ],
            'output_handler' => $this->outputHandler !== null ? get_class($this->outputHandler) : null,
            'events' => [
                'on_start' => $this->onStartCallback instanceof Closure ? 'Closure' : null,
                'on_complete' => $this->onCompleteCallback instanceof Closure ? 'Closure' : null,
                'on_error' => $this->onErrorCallback instanceof Closure ? 'Closure' : null,
            ],
        ];
    }
}

abstract class ProcessOutputHandler
{
    abstract public function write(ProcessResult $result): void;

    /**
     * @return array<string, mixed>
     */
    abstract public function configuration(): array;
}

final class FileOutputHandler extends ProcessOutputHandler
{
    public function __construct(private string $path) {}

    public function write(ProcessResult $result): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $result->toJson() . "\n---\n";
        file_put_contents($this->path, $content, FILE_APPEND);
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        return ['path' => $this->path];
    }
}

final class DatabaseOutputHandler extends ProcessOutputHandler
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $table,
        private array $metadata
    ) {}

    public function write(ProcessResult $result): void
    {
        if ($this->table === '' || !class_exists(\Memran\MarwaDb\QueryBuilder::class)) {
            return;
        }

        try {
            \Memran\MarwaDb\QueryBuilder::table($this->table)->insert([
                'command' => $result->getCommand(),
                'exit_code' => $result->getExitCode(),
                'output' => $result->getOutput(),
                'error' => $result->getError(),
                'duration' => $result->getDuration(),
                'memory' => $result->getMemory(),
                'started_at' => $result->getStartTime(),
                'completed_at' => $result->getEndTime(),
                'metadata' => json_encode($this->metadata, JSON_THROW_ON_ERROR),
                'created_at' => new \DateTimeImmutable(),
            ]);
        } catch (\Throwable $e) {
            error_log('Process DB output error: ' . $e->getMessage());
        }
    }

    /**
     * @return array{table: string, metadata: array<string, mixed>}
     */
    public function configuration(): array
    {
        return ['table' => $this->table, 'metadata' => $this->metadata];
    }
}

final class RedisOutputHandler extends ProcessOutputHandler
{
    public function __construct(
        private string $key,
        private int $ttl
    ) {}

    public function write(ProcessResult $result): void
    {
        if ($this->key === '' || !class_exists(\Memran\MarwaDb\Redis::class)) {
            return;
        }

        try {
            $redis = \Memran\MarwaDb\Redis::connection();
            $fullKey = 'process:' . $this->key;

            $redis->setex($fullKey, $this->ttl, $result->toJson());

            $historyKey = $fullKey . ':history';
            $redis->rpush($historyKey, $result->toJson());
            $redis->ltrim($historyKey, -100, -1);
        } catch (\Throwable $e) {
            error_log('Process Redis output error: ' . $e->getMessage());
        }
    }

    /**
     * @return array{key: string, ttl: int}
     */
    public function configuration(): array
    {
        return ['key' => $this->key, 'ttl' => $this->ttl];
    }
}
