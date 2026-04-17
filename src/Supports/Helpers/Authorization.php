<?php

declare(strict_types=1);

use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Exceptions\AuthorizationException;

if (!function_exists('authorize')) {
    function authorize(string $ability, mixed $resource = null): bool
    {
        $gate = app(GateInterface::class);

        return $gate->authorize($ability, $resource);
    }
}

if (!function_exists('can')) {
    function can(string $ability, mixed $resource = null): bool
    {
        $gate = app(GateInterface::class);

        return $gate->check($ability, $resource);
    }
}

if (!function_exists('cannot')) {
    function cannot(string $ability, mixed $resource = null): bool
    {
        return !can($ability, $resource);
    }
}

if (!function_exists('gate')) {
    function gate(): GateInterface
    {
        return app(GateInterface::class);
    }
}