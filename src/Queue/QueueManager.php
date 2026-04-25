<?php

declare(strict_types=1);

namespace Marwa\Framework\Queue;

use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Supports\Config;

final class QueueManager
{
    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    public function resolve(): QueueInterface
    {
        $settings = $this->configuration();

        $queue = match ($settings['driver']) {
            'database' => $this->app->make(DatabaseQueue::class),
            'file' => $this->app->make(FileQueue::class),
            default => $this->app->make(FileQueue::class),
        };

        if (!$queue instanceof QueueInterface) {
            throw new \RuntimeException(sprintf('Queue driver [%s] did not resolve a queue implementation.', $settings['driver']));
        }

        return $queue;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     default: string,
     *     path: string,
     *     database: array{connection: string, table: string},
     *     retryAfter: int,
     *     tries: int|null
     * }
     */
    public function configuration(): array
    {
        $this->config->loadIfExists(QueueConfig::KEY . '.php');

        return QueueConfig::merge($this->app, $this->config->getArray(QueueConfig::KEY, []));
    }
}
