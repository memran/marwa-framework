<?php


namespace Marwa\Application\Configs\Interfaces;


interface ConfigClassInterface
{
    public function setFile(string $file):ConfigClassInterface;
    public function load():array;
}
