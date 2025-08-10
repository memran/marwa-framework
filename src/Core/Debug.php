<?php

declare(strict_types=1);

namespace Marwa\App\Core;

use Whoops\Run as WhoopsRun;
use Whoops\Handler\{JsonResponseHandler, PlainTextHandler, PrettyPageHandler};
use Marwa\App\Support\Runtime;


final class Debug
{
    private static bool $enabled = false;
    private static ?WhoopsRun $whoops = null;

    /**
     * Enable pretty error pages (HTML or JSON)
     *
     * @param bool $jsonMode If true, render JSON errors instead of HTML
     */
    public static function enable(bool $jsonMode = false): void
    {
        if (self::$enabled) {
            return;
        }

        $whoops = new WhoopsRun();
        if (Runtime::isWeb()) {
            $handler = new PrettyPageHandler();
            $handler->setPageTitle('Application Error');
            $whoops->pushHandler($handler);
        } else if (Runtime::isConsole()) {
            $handler = new PlainTextHandler();
            $handler->addTraceToOutput(true);
            $whoops->pushHandler($handler);
        } elseif ($jsonMode) {
            $handler = new JsonResponseHandler();
            $handler->addTraceToOutput(true);
            $whoops->pushHandler($handler);
        } else {
            $handler = new PrettyPageHandler();
            $handler->setPageTitle('Application Error');
            $whoops->pushHandler($handler);
        }
        $whoops->register();
        self::$whoops  = $whoops;
        self::$enabled = true;
    }

    /**
     * Disable Whoops and restore default PHP error handling
     */
    public static function disable(): void
    {
        if (self::$whoops instanceof WhoopsRun) {
            self::$whoops->unregister();
        }
        restore_error_handler();
        restore_exception_handler();
        self::$enabled = false;
        self::$whoops  = null;
    }

    /**
     * Check if debug mode is currently enabled
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }
}
