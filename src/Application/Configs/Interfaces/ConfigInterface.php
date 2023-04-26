<?php


namespace Marwa\Application\Configs\Interfaces;


interface ConfigInterface
{
    public function file(string $filename): ConfigInterface;
    public function load(string $filename=null): array;
    public static function getInstance(string $filename=null):ConfigInterface;
    public function getType():string;
    public function setType(string $type);
    public function setConfigDir(string $path): ConfigInterface;
    public function getConfigDir():string;
}
