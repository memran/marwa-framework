<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

use Marwa\Framework\Scheduling\Stores\ScheduleStoreInterface;

interface ScheduleStoreResolverInterface
{
    /**
     * @param array{
     *     driver:string,
     *     file:array{path:string},
     *     cache:array{namespace:string},
     *     database:array{connection:string,table:string}
     * } $config
     */
    public function resolve(array $config): ScheduleStoreInterface;
}
