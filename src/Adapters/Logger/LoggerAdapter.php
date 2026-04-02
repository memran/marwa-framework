<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Logger;

use Marwa\Framework\Application;
use Marwa\Framework\Config\LoggerConfig;
use Marwa\Framework\Supports\Config;
use Marwa\Logger\Contracts\SinkInterface;
use Marwa\Logger\SimpleLogger;
use Marwa\Logger\Storage\StorageFactory;
use Marwa\Logger\Support\SensitiveDataFilter;

/**
 * PSR-3 logger adapter over memran/marwa-logger.
 */
final class LoggerAdapter
{
    protected SimpleLogger $logger;
    protected SensitiveDataFilter $filter;
    protected SinkInterface $sink;

    public function __construct(private Application $app, private Config $config)
    {
        $this->config->loadIfExists(LoggerConfig::KEY . '.php');
        $this->boot();
    }

    /**
     * Boot and Configure the Logger
     */
    private function boot(): void
    {
        $defaults = LoggerConfig::defaults($this->app);

        /** @var list<string> $filter */
        $filter = $this->config->getArray(LoggerConfig::KEY . '.filter', $defaults['filter']);
        $this->setFilter($filter);

        /** @var array<string, mixed> $storage */
        $storage = $this->config->getArray(LoggerConfig::KEY . '.storage', $defaults['storage']);
        $this->setStorage($storage);

        $this->logger = new SimpleLogger(
            appName: env('APP_NAME', 'MarwaPHP'),
            env: env('APP_ENV', 'production'),
            sink: $this->sink,
            filter: $this->filter,
            logging: $this->config->getBool(LoggerConfig::KEY . '.enable', $defaults['enable'])
        );
    }

    public function getLogger(): SimpleLogger
    {
        return $this->logger;
    }

    /**
     * @param list<string> $filter
     */
    public function setFilter(array $filter): void
    {
        $this->filter = new SensitiveDataFilter($filter);
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    public function setStorage(array $storageConfig): void
    {
        $this->sink = StorageFactory::make($storageConfig);
    }

    /**
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): mixed
    {
        if (!method_exists($this->logger, $method)) {
            throw new \BadMethodCallException(sprintf('Method "%s" does not exist on %s.', $method, $this->logger::class));
        }

        return $this->logger->{$method}(...$args);
    }
}
