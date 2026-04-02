<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling\Stores;

use Marwa\Framework\Bootstrappers\DatabaseBootstrapper;
use Marwa\Framework\Contracts\CacheInterface;

final class ScheduleStoreResolver
{
    /**
     * @var array<string, ScheduleStoreInterface>
     */
    private array $stores = [];

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

        if (isset($this->stores[$driver])) {
            return $this->stores[$driver];
        }

        return $this->stores[$driver] = match ($driver) {
            'cache' => new CacheScheduleStore(
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
