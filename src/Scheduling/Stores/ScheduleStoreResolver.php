<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Contracts\ScheduleStoreResolverInterface;

final class ScheduleStoreResolver implements ScheduleStoreResolverInterface
{
    public function __construct(
        private DatabaseBootstrapper $databaseBootstrapper,
        private CacheInterface $cache
    ) {}

    /**
     * @param array{
     *     driver:string,
     *     file:array{path:string},
     *     cache:array{namespace:string},
     *     database:array{connection:string,table:string}
     * } $config
     */
    public function resolve(array $config): ScheduleStoreInterface
    {
        $driver = $config['driver'];

        return match ($driver) {
            'cache' => new CacheScheduleStore(
                $this->cache,
                $config['cache']['namespace']
            ),
            'redis' => new RedisScheduleStore(
                $this->cache,
                $config['cache']['namespace']
            ),
            'database' => new DatabaseScheduleStore(
                $this->databaseBootstrapper,
                $config['database']['connection'],
                $config['database']['table']
            ),
            default => new FileScheduleStore($config['file']['path']),
        };
    }
}
