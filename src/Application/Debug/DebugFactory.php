<?php


namespace Marwa\Application\Debug;


class DebugFactory
{

    public static function create(string $type)
    {
        return new WhoopsDebug();
    }
}
