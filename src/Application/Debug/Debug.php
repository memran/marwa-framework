<?php


namespace Marwa\Application\Debug;

class Debug
{

    public static function getInstance()
    {
        return DebugFactory::create('whoops');
    }
}
