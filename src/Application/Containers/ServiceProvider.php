<?php

namespace Marwa\Application\Containers;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

abstract class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{

    /**
     * @param string $id
     * @param null   $concrete
     */
    public function singleton(string $id, $concrete = null)
    {
        app()->getContainer()->singleton($id, $concrete);
    }

    /**
     * @param string    $id
     * @param null      $concrete
     * @param bool|null $shared
     */
    public function bind(string $id, $concrete = null, bool $shared = null)
    {
        app()->getContainer()->bind($id, $concrete, $shared);
    }

    /**
     *
     */
    public function boot()
    {

    }

}
