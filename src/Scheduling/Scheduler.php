<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling;

use Marwa\Framework\Application;
use Marwa\Framework\Config\ScheduleConfig;
use Marwa\Framework\Queue\FileQueue;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreInterface;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreResolver;
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
     * @var array{
     *     enabled:bool,
     *     driver:string,
     *     lockPath:string,
     *     file:array{path:string},
     *     cache:array{namespace:string},
     *     database:array{connection:string,table:string},
     *     defaultLoopSeconds:int,
     *     defaultSleepSeconds:int
     * }|null
     */
    private ?array $config = null;

    private ?ScheduleStoreInterface $store = null;

    public function __construct(
        private Application $app,
        private LoggerInterface $logger,
        private FileQueue $queue,
        private ScheduleStoreResolver $storeResolver
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
            $store = $this->store();

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
        if ($this->config !== null) {
            return $this->config;
        }

        /** @var \Marwa\Framework\Supports\Config $config */
        $config = $this->app->make(\Marwa\Framework\Supports\Config::class);
        $config->loadIfExists(ScheduleConfig::KEY . '.php');
        $this->config = ScheduleConfig::merge($this->app, $config->getArray(ScheduleConfig::KEY, []));

        return $this->config;
    }

    private function store(): ScheduleStoreInterface
    {
        if ($this->store !== null) {
            return $this->store;
        }

        $this->store = $this->storeResolver->resolve($this->configuration());

        return $this->store;
    }
}
