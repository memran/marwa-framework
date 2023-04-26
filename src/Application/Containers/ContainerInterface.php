<?php


namespace Marwa\Application\Containers;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface
{
    public static function getInstance():ContainerInterface;
    public function getPsrContainer():PsrContainerInterface;
    public function bind(string $id, $concrete = null, bool $shared = null);
    public function singleton(string $id, $concrete = null);
    public function get($id, bool $new = false);
    public function enableAutoWire(bool $cache=false):ContainerInterface;
    public function addServiceProvider($provider):ContainerInterface;

}
