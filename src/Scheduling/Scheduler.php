<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling;

use Marwa\Framework\Application;
use Marwa\Framework\Config\ScheduleConfig;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Contracts\ScheduleStoreResolverInterface;
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
     * @param array{ran:list<string>,skipped:list<string>,failed:list<string>} $summary
     * @return array{ran:list<string>,skipped:list<string>,failed:list<string>}
     */
    private function runTasks(\DateTimeImmutable $currentTime, array $summary): array
    {
        $store = $this->store();

        foreach ($this->due($currentTime) as $task) {
            $lock = null;

            if ($task->shouldPreventOverlaps()) {
                $lock = $store->acquireLock($task, $currentTime, max(60, $task->intervalSeconds() * 2));

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
