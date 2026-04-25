<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Process;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\Process\ProcessResult;
use Marwa\Framework\Supports\Config;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class ProcessAdapter
{
    private array $options = [];
    private ?ProcessOutputHandler $outputHandler = null;
    private ?string $input = null;
    private int $retryAttempts = 0;
    private int $retryDelayMs = 1000;
    private ?callable $onStartCallback = null;
    private ?callable $onCompleteCallback = null;
    private ?callable $onErrorCallback = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    public function __invoke(string $command, array $options = []): ProcessResult
    {
        return $this->execute($command, $options);
    }

    public function run(array $options = []): ProcessResult
    {
        $command = $this->options['command'] ?? '';
        return $this->execute($command, $options);
    }

    public function execute(string $command, array $options = []): ProcessResult
    {
        $options = array_merge($this->options, $options);
        
        $timeout = $options['timeout'] ?? $this->configuration()['timeout'];
        $cwd = $options['cwd'] ?? $this->configuration()['cwd'];
        $env = $options['env'] ?? [];
        
        $maxAttempts = $options['retry'] ?? $this->retryAttempts;
        $attempt = 0;
        
        do {
            $startTime = microtime(true);
            
            if ($this->onStartCallback) {
                ($this->onStartCallback)($command);
            }
            
            try {
                $process = Process::fromShellCommandLine($command, $cwd, $env, null, $timeout);
                
                if ($this->input) {
                    $process->setInput($this->input);
                }
                
                $process->run();
                
                $result = ProcessResult::fromSymfonyProcess($process, $startTime);
                
                if ($this->onCompleteCallback) {
                    ($this->onCompleteCallback)($result);
                }
                
                if ($this->outputHandler) {
                    $this->outputHandler->write($result);
                }
                
                if ($result->isSuccessful() || $attempt >= $maxAttempts) {
                    return $result;
                }
                
            } catch (\Throwable $e) {
                $result = ProcessResult::error($command, $e->getMessage(), microtime(true) - $startTime);
                
                if ($this->onErrorCallback) {
                    ($this->onErrorCallback)($e);
                }
                
                if ($this->outputHandler) {
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
        
        return $result ?? ProcessResult::error($command, 'Max retries exceeded');
    }

    public function parallel(array $commands, array $options = []): ProcessResult
    {
        $startTime = microtime(true);
        
        $processes = [];
        foreach ($commands as $cmd) {
            $processes[] = Process::fromShellCommandLine($cmd);
        }
        
        // Run processes concurrently
        foreach ($processes as $process) {
            $process->start();
        }
        
        // Wait for all to finish
        while ($processes) {
            foreach ($processes as $index => $process) {
                if (!$process->isRunning()) {
                    unset($processes[$index]);
                }
            }
            if ($processes) {
                usleep(100000);
            }
        }
        
        $outputs = [];
        $exitCodes = [];
        
        foreach ($processes as $i => $process) {
            $outputs[$i] = $process->getOutput();
            $exitCodes[$i] = $process->getExitCode();
        }
        
        $result = new ProcessResult(
            'parallel: ' . implode(' && ', $commands),
            array_sum($exitCodes) === 0 ? 0 : -1,
            json_encode($outputs),
            '',
            microtime(true) - $startTime,
            memory_get_peak_usage(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
        
        if ($this->outputHandler) {
            $this->outputHandler->write($result);
        }
        
        return $result;
    }

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

    public function env(array $env): self
    {
        $this->options['env'] = $env;
        return $this;
    }

    public function onStart(callable $callback): self
    {
        $this->onStartCallback = $callback;
        return $this;
    }

    public function onComplete(callable $callback): self
    {
        $this->onCompleteCallback = $callback;
        return $this;
    }

    public function onError(callable $callback): self
    {
        $this->onErrorCallback = $callback;
        return $this;
    }

    public function toFile(string $path): self
    {
        $this->outputHandler = new FileOutputHandler($path);
        return $this;
    }

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

    private function buildPayload(): array
    {
        return [
            'command' => $this->options['command'] ?? '',
            'options' => [
                'timeout' => $this->options['timeout'] ?? null,
                'cwd' => $this->options['cwd'] ?? null,
                'env' => $this->options['env'] ?? [],
                'retry' => $this->retryAttempts,
            ],
            'output_handler' => $this->outputHandler !== null ? get_class($this->outputHandler) : null,
            'events' => [
                'on_start' => $this->onStartCallback ? ' Closure' : null,
                'on_complete' => $this->onCompleteCallback ? ' Closure' : null,
                'on_error' => $this->onErrorCallback ? ' Closure' : null,
            ],
        ];
    }
}

abstract class ProcessOutputHandler
{
    abstract public function write(ProcessResult $result): void;
    
    abstract public function configuration(): array;
}

class FileOutputHandler extends ProcessOutputHandler
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
    
    public function configuration(): array
    {
        return ['path' => $this->path];
    }
}

class DatabaseOutputHandler extends ProcessOutputHandler
{
    public function __construct(
        private string $table,
        private array $metadata
    ) {}
    
    public function write(ProcessResult $result): void
    {
        if (!$this->table || !class_exists(\Memran\MarwaDb\QueryBuilder::class)) {
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
                'metadata' => json_encode($this->metadata),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            error_log('Process DB output error: ' . $e->getMessage());
        }
    }
    
    public function configuration(): array
    {
        return ['table' => $this->table, 'metadata' => $this->metadata];
    }
}

class RedisOutputHandler extends ProcessOutputHandler
{
    public function __construct(
        private string $key,
        private int $ttl
    ) {}
    
    public function write(ProcessResult $result): void
    {
        if (!$this->key || !class_exists(\Memran\MarwaDb\Redis::class)) {
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
    
    public function configuration(): array
    {
        return ['key' => $this->key, 'ttl' => $this->ttl];
    }
}