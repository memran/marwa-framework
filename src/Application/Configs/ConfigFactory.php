<?php


namespace Marwa\Application\Configs;


use Marwa\Application\Configs\Interfaces\ConfigClassInterface;

class ConfigFactory implements Interfaces\ConfigFactoryInterface
{

    public static function create(string $type): ConfigClassInterface
    {
            return new PhpArrayClass();
    }
}
