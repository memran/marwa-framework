<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\ErrorHandler\ErrorHandler;
use Marwa\ErrorHandler\Support\FallbackRenderer;

class ErrorHandlerAdapter
{

    public static function boot()
    {
        $errorHandler = new ErrorHandler(appName: env('APP_NAME', 'MyApp'), env: env('APP_ENV', 'development'));
        $errorHandler->setRenderer(new FallbackRenderer());
        $errorHandler->register();
        if (env('APP_ENV') === 'production') {
            $errorHandler->setLogger(logger());
        }
    }
}
