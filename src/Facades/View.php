<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Views\View as FrameworkView;

final class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FrameworkView::class;
    }
}
