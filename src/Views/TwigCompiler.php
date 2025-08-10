<?php

namespace Marwa\App\Views;

class TwigCompiler
{
    public static function compile(ViewFactory $factory)
    {
        $factory->addExtension(new ViewExtensions());
    }
}
