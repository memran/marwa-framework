<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling;

use Marwa\Framework\Application;
use Marwa\Framework\Config\ScheduleConfig;
use Marwa\Framework\Queue\FileQueue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class Scheduler
{
    /**
     * @var list<Task>
     */
    private array $tasks = [];

    /**
     * @var array{enabled:bool,lockPath:string,defaultLoopSeconds:int,defaultSleepSeconds:int}|null
     */
    private ?array $config = null;

    public function __construct(
        private Application $app,
        private LoggerInterface $logger,
        private FileQueue $queue
    ) {}

    public function register(Task $task): Task
    {
        $this->tasks[] = $task;

        return $task;
    }

    /**
     * @param callable(Application, \DateTimeImmutable): mixed $callback
     */
    public function call(callable $callback, ?string $name = null): Task
    {
        $task = new Task($name ?? 'callback-' . (count($this->tasks) + 1), $callback);

        return $this->register($task);
    }

    /**
     * @param array<string, scalar|array<int|string, scalar|null>|null> $arguments
     */
    public function command(string $command, array $arguments = [], ?string $name = null): Task
    {
        return $this->register(new Task($name ?? 'command:' . $command, function () use ($command, $arguments): int {
            $console = $this->app->console()->application();
            $resolved = $console->find($command);
            $input = new ArrayInput(['command' => $command, ...$arguments]);

            return $resolved->run($input, new BufferedOutput());
        }));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function queue(string $job, array $payload = [], ?string $queue = null, ?string $name = null): Task
    {
        return $this->register(new Task($name ?? 'queue:' . $job, function () use ($job, $payload, $queue): void {
            $this->queue->push($job, $payload, $queue);
        }));
    }

    /**
     * @return list<Task>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return list<Task>
     */
    public function due(\DateTimeImmutable $time): array
    {
        return array_values(array_filter(
            $this->tasks,
            fn (Task $task): bool => $task->isDue($this->app, $time)
        ));
    }

    /**
     * @return array{ran:list<string>,skipped:list<string>,failed:list<string>}
     */
    public function runDue(?\DateTimeImmutable $time = null): array
    {
        $currentTime = $time ?? new \DateTimeImmutable();
        $summary = [
            'ran' => [],
            'skipped' => [],
            'failed' => [],
        ];

        if (!$this->configuration()['enabled']) {
            return $summary;
        }

        foreach ($this->due($currentTime) as $task) {
            $lock = null;

            if ($task->shouldPreventOverlaps()) {
                $lock = $this->acquireLock($task);

                if ($lock === null) {
                    $summary['skipped'][] = $task->name();
                    continue;
                }
            }

            try {
                $task->run($this->app, $currentTime);
                $summary['ran'][] = $task->name();
            } catch (\Throwable $exception) {
                $summary['failed'][] = $task->name();
                $this->logger->error('Scheduled task failed.', [
                    'task' => $task->name(),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } finally {
                if (is_resource($lock)) {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            }
        }

        return $summary;
    }

    /**
     * @return array{enabled:bool,lockPath:string,defaultLoopSeconds:int,defaultSleepSeconds:int}
     */
    public function configuration(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        /** @var \Marwa\Framework\Supports\Config $config */
        $config = $this->app->make(\Marwa\Framework\Supports\Config::class);
        $config->loadIfExists(ScheduleConfig::KEY . '.php');
        $this->config = ScheduleConfig::merge($this->app, $config->getArray(ScheduleConfig::KEY, []));

        return $this->config;
    }

    /**
     * @return resource|null
     */
    private function acquireLock(Task $task)
    {
        $directory = $this->configuration()['lockPath'];

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
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

        return $handle;
    }

    private function normalizeName(string $name): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_\-]+/', '-', strtolower($name)) ?: 'task';

        return trim($normalized, '-');
    }
}
