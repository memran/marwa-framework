<?php

declare(strict_types=1);

namespace Marwa\Framework\Controllers\Concerns;

use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Exceptions\AuthorizationException;

trait AuthorizesRequests
{
    protected function authorize(string $ability, mixed $resource = null): bool
    {
        $gate = $this->getGate();

        return $gate->authorize($ability, $resource);
    }

    protected function authorizeTo(string $ability, mixed $resource = null): void
    {
        $gate = $this->getGate();

        $gate->authorize($ability, $resource);
    }

    protected function can(string $ability, mixed $resource = null): bool
    {
        $gate = $this->getGate();

        return $gate->check($ability, $resource);
    }

    protected function cannot(string $ability, mixed $resource = null): bool
    {
        return !$this->can($ability, $resource);
    }

    /**
     * @return array<string, string>
     */
    protected function authorizeResource(string $modelClass, ?string $parameterKey = null): array
    {
        $modelName = $this->getModelName($modelClass, $parameterKey);
        $abilities = [
            'index' => $modelName . '.viewAny',
            'create' => $modelName . '.create',
            'store' => $modelName . '.create',
            'show' => $modelName . '.view',
            'edit' => $modelName . '.update',
            'update' => $modelName . '.update',
            'destroy' => $modelName . '.delete',
        ];

        return $abilities;
    }

    protected function authorizeClass(string $ability): bool
    {
        $gate = $this->getGate();

        return $gate->authorize($ability);
    }

    protected function getGate(): GateInterface
    {
        return app(GateInterface::class);
    }

    private function getModelName(string $modelClass, ?string $parameterKey = null): string
    {
        if ($parameterKey !== null) {
            return $parameterKey;
        }

        $parts = explode('\\', $modelClass);

        return strtolower(end($parts));
    }
}
