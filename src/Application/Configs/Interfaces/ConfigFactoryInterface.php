<?php


namespace Marwa\Application\Configs\Interfaces;


interface ConfigFactoryInterface
{
    public static function create(string $type):ConfigClassInterface;
}
