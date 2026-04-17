<?php

declare(strict_types=1);

/**
 * View and Debug Helper Functions
 */

if (!function_exists('view')) {
    /**
     * @param array<string, mixed> $params
     */
    function view(string $tplName = '', array $params = []): mixed
    {
        /** @var \Marwa\Framework\Views\View $view */
        $view = app(\Marwa\Framework\Views\View::class);

        if ($tplName !== '') {
            return $view->make($tplName, $params);
        }

        return $view;
    }
}

if (!function_exists('image')) {
    function image(?string $path = null): \Marwa\Framework\Supports\Image
    {
        if ($path !== null && trim($path) !== '') {
            return \Marwa\Framework\Supports\Image::fromFile($path);
        }

        return \Marwa\Framework\Supports\Image::canvas(1, 1, '#00000000');
    }
}

if (!function_exists('debugger')) {
    function debugger(): mixed
    {
        if (config('app.debugbar', false)) {
            if (app()->has('debugbar')) {
                return app('debugbar');
            }
        }

        return null;
    }
}

if (!function_exists('is_local')) {
    function is_local(): bool
    {
        $app = app();

        return $app->environment('local') === true || $app->environment('development') === true;
    }
}

if (!function_exists('is_production')) {
    function is_production(): bool
    {
        return app()->environment('production') === true;
    }
}

if (!function_exists('running_in_console')) {
    function running_in_console(): bool
    {
        return \Marwa\Framework\Supports\Runtime::isConsole();
    }
}

if (!function_exists('notify')) {
    /**
     * @return array<string, mixed>
     */
    function notify(\Marwa\Framework\Contracts\NotificationInterface $notification, ?object $notifiable = null): array
    {
        return notification()->send($notification, $notifiable);
    }
}
