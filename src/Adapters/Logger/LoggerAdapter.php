<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Logger;

use Marwa\Logger\SimpleLogger;
use Marwa\Logger\Support\SensitiveDataFilter;
use Marwa\Logger\Storage\StorageFactory;
use Marwa\Logger\Contracts\SinkInterface;
use Marwa\Framework\Facades\Config;

/**
 * PSR-3 logger adapter over memran/marwa-logger.
 */
final class LoggerAdapter
{
    protected SimpleLogger $logger;

    /**
     * 
     */
    protected Config $config;
    /**
     * 
     */
    protected SensitiveDataFilter $filter;
    /**
     * 
     */
    protected SinkInterface $sink;

    /**
     * 
     */
    public function __construct()
    {
        Config::load('logger.php');
        $this->boot();
    }
    /**
     * Boot and Configure the Logger
     */
    private function boot(): void
    {
        //set Sensitive Filter
        $this->setFilter(Config::getArray('logger.filter'));
        //set Storage
        $this->setStorage(Config::getArray('logger.storage'));

        $this->logger = new SimpleLogger(
            appName: env('APP_NAME', 'myapp'),
            env: env('APP_ENV', 'production'),
            sink: $this->sink,
            filter: $this->filter,
            logging: Config::getBool('logger.enable', false)               // true in development
        );
    }
    /**
     * 
     */
    public function getLogger(): SimpleLogger
    {
        return $this->logger;
    }
    /**
     * 
     */
    public function setFilter(array $filter): void
    {
        $this->filter = new SensitiveDataFilter($filter);
    }
    /**
     * 
     */
    public function setStorage(array $storageConfig): void
    {
        $this->sink =  StorageFactory::make($storageConfig);
    }
    /**
     * 
     */
    public function __call($method, $args)
    {
        if (method_exists($this->logger, $method)) {
            throw new \BadMethodCallException();
        }

        return call_user_func_array([$this->logger, $method], $args);
    }
}
