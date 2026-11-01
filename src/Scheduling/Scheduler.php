<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling;

use Marwa\Framework\Application;
use Marwa\Framework\Config\ScheduleConfig;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Contracts\ScheduleStoreResolverInterface;
use Marwa\Framework\Queue\NonRetryableJobException;
use Marwa\Framework\Queue\QueuedJob;
use Marwa\Framework\Scheduling\Stores\DatabaseScheduleStore;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class Scheduler
{
    /**
     * @var list<Task>
     */
    private array $tasks = [];

    public function __construct(
        private Application $app,
        private LoggerInterface $logger,
        private QueueInterface $queue,
        private ScheduleStoreResolverInterface $storeResolver
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
        $taskName = $name ?? 'callback-' . bin2hex(random_bytes(8));
        $task = new Task($taskName, $callback);

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

            $exit = $resolved->run($input, new BufferedOutput());
            if ($exit !== 0) {
                throw new \RuntimeException(sprintf('Scheduled command [%s] exited with code %d.', $command, $exit));
            }
            return $exit;
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
        $dueTasks = [];
        $errors = [];

        foreach ($this->tasks as $task) {
            try {
                if ($task->isDue($this->app, $time)) {
                    $dueTasks[] = $task;
                }
            } catch (\Throwable $e) {
                $errors[$task->name()] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            $this->logger->warning('Some tasks threw exceptions during due check.', [
                'errors' => $errors,
            ]);
        }

        return $dueTasks;
    }

    /**
     * @return array{ran:list<string>,queued:list<string>,skipped:list<string>,failed:list<string>}
     */
    public function runDue(?\DateTimeImmutable $time = null): array
    {
        $currentTime = $time ?? new \DateTimeImmutable();
        $summary = [
            'ran' => [],
            'queued' => [],
            'skipped' => [],
            'failed' => [],
        ];

        if (!$this->configuration()['enabled']) {
            return $summary;
        }

        try {
            return $this->runTasks($currentTime, $summary);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to run scheduled tasks.', [
                'exception' => $e->getMessage(),
            ]);

            return $summary;
        }
    }

    /**
     * @param array{ran:list<string>,queued:list<string>,skipped:list<string>,failed:list<string>} $summary
     * @return array{ran:list<string>,queued:list<string>,skipped:list<string>,failed:list<string>}
     */
    private function runTasks(\DateTimeImmutable $currentTime, array $summary): array
    {
        $store = null;

        foreach ($this->due($currentTime) as $task) {
            if ($task->runsInBackground()) {
                try {
                    $queued = $this->dispatch($task->name(), $currentTime->getTimestamp());
                    $summary[$queued === null ? 'skipped' : 'queued'][] = $task->name();
                } catch (\Throwable $error) {
                    $summary['failed'][] = $task->name();
                    $this->logger->error('Scheduled task dispatch failed.', ['task' => $task->name(), 'exception' => $error->getMessage()]);
                }
                continue;
            }
            $lock = null;
            try {
                $store ??= $this->store();
                if ($task->shouldPreventOverlaps()) {
                    $lock = $store->acquireLock($task, $currentTime, max(60, $task->intervalSeconds() * 2));
                }
            } catch (\Throwable $error) {
                $summary['failed'][] = $task->name();
                $this->logger->error('Scheduler storage unavailable; task was not executed.', ['task' => $task->name(), 'exception' => $error->getMessage()]);
                continue;
            }

            if ($task->shouldPreventOverlaps()) {

                if ($lock === null) {
                    $summary['skipped'][] = $task->name();
                    $store->record($task, $currentTime, 'skipped', 'Skipped because it is already running.');
                    continue;
                }
            }

            try {
                $task->run($this->app, $currentTime);
                $summary['ran'][] = $task->name();
                $store->record($task, $currentTime, 'success');
            } catch (\Throwable $exception) {
                $summary['failed'][] = $task->name();
                $store->record($task, $currentTime, 'failed', $exception->getMessage());
                $this->logger->error('Scheduled task failed.', [
                    'task' => $task->name(),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } finally {
                $store->releaseLock($task, $lock);
            }
        }

        return $summary;
    }

    /** Queue a registered task; the reservation covers both waiting and execution. */
    public function dispatch(string $name, ?int $occurrence = null): ?QueuedJob
    {
        if (!$this->configuration()['enabled']) {
            throw new \RuntimeException('The scheduler is disabled.');
        }
        $task = $this->findTask($name);
        $store = $this->store();
        if (!$store instanceof DatabaseScheduleStore) {
            throw new \RuntimeException('Background task dispatch requires the database scheduler store.');
        }
        $settings = $this->app->make(\Marwa\Framework\Supports\Config::class);
        $lease = max(60, (int) $settings->get('schedule.backgroundLeaseSeconds', 900));
        $timeout = (int) $settings->get('queue.jobTimeout', 0);
        if ($timeout <= 0 || $timeout >= $lease) {
            throw new \RuntimeException('Background tasks require a positive queue job timeout shorter than their lease.');
        }
        $time = new \DateTimeImmutable();
        $token = $store->acquireLock($task, $time, $lease, $occurrence);
        if (!is_string($token)) {
            return null;
        }
        try {
            $job = $this->queue->push(ScheduledTaskJob::class, ['task' => $name, 'token' => $token]);
            $store->record($task, $time, 'queued');
            return $job;
        } catch (\Throwable $error) {
            try {
                $store->record($task, new \DateTimeImmutable(), 'failed', 'Task could not be queued.');
            } finally {
                $store->releaseLock($task, $token, true);
            }
            throw $error;
        }
    }

    public function runReserved(string $name, string $token): void
    {
        $task = $this->findTask($name);
        $store = $this->store();
        if (!$store instanceof DatabaseScheduleStore) {
            throw new NonRetryableJobException('Background tasks require the database scheduler store.');
        }
        $time = new \DateTimeImmutable();
        if (!$this->configuration()['enabled'] || !$store->adoptLock($task, $token, $time)) {
            if ($store->adoptLock($task, $token, $time, true)) {
                $store->record($task, $time, 'failed', 'Task reservation expired or scheduler disabled.');
            }
            $store->releaseLock($task, $token);
            throw new NonRetryableJobException('Task reservation expired or scheduler disabled; task was not executed.');
        }
        try {
            $store->record($task, $time, 'running');
            $task->run($this->app, $time);
            $store->record($task, new \DateTimeImmutable(), 'success');
        } catch (\Throwable $error) {
            $store->record($task, new \DateTimeImmutable(), 'failed', $error->getMessage());
            throw new NonRetryableJobException($error->getMessage(), 0, $error);
        } finally {
            $store->releaseLock($task, $token);
        }
    }

    private function findTask(string $name): Task
    {
        foreach ($this->tasks as $task) {
            if ($task->name() === $name) {
                return $task;
            }
        }
        throw new NonRetryableJobException('Scheduled task is no longer registered.');
    }

    /**
     * @return array{
     *     enabled:bool,
     *     driver:string,
     *     lockPath:string,
     *     file:array{path:string},
     *     cache:array{namespace:string},
     *     database:array{connection:string,table:string},
     *     defaultLoopSeconds:int,
     *     defaultSleepSeconds:int
     * }
     */
    public function configuration(): array
    {
        /** @var \Marwa\Framework\Supports\Config $config */
        $config = $this->app->make(\Marwa\Framework\Supports\Config::class);
        $config->loadIfExists(ScheduleConfig::KEY . '.php');

        return ScheduleConfig::merge($this->app, $config->getArray(ScheduleConfig::KEY, []));
    }

    private function store(): ScheduleStoreInterface
    {
        return $this->storeResolver->resolve($this->configuration());
    }
}
