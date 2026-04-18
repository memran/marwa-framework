<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization;

use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Authorization\Contracts\UserInterface;
use Marwa\Framework\Exceptions\AuthorizationException;

class Gate implements GateInterface
{
    protected PolicyRegistry $registry;

    protected ?UserInterface $user = null;

    /**
     * @var array<string, callable>
     */
    protected array $callbacks = [];

    protected bool $beforeCallbacksDisabled = false;

    public function __construct(PolicyRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function authorize(string $ability, mixed $resource = null): bool
    {
        $result = $this->check($ability, $resource);

        if ($result === false) {
            throw new AuthorizationException(
                'Unauthorized',
                $ability,
                $resource
            );
        }

        return true;
    }

    public function check(string $ability, mixed $resource = null): bool
    {
        if ($this->user === null) {
            return false;
        }

        if ($this->beforeCallbacksDisabled === false) {
            foreach ($this->callbacks as $callback) {
                $result = $callback($this->user, $ability, $resource);

                if ($result !== null) {
                    return $result;
                }
            }
        }

        $policy = $this->getPolicyForResource($resource);

        if ($policy === null) {
            $permissionString = $this->buildPermissionString($ability, $resource);

            return $this->user->hasPermission($permissionString);
        }

        $method = $this->resolveMethod($ability);

        if (!method_exists($policy, $method)) {
            $permissionString = $this->buildPermissionString($ability, $resource);

            return $this->user->hasPermission($permissionString);
        }

        return $policy->{$method}($this->user, $resource);
    }

    public function denies(string $ability, mixed $resource = null): bool
    {
        return !$this->check($ability, $resource);
    }

    public function allows(string $ability, mixed $resource = null): bool
    {
        return $this->check($ability, $resource);
    }

    public function forUser(UserInterface $user): GateInterface
    {
        $gate = new Gate($this->registry);
        $gate->setUser($user);
        $gate->callbacks = $this->callbacks;

        return $gate;
    }

    public function before(callable $callback): self
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    public function define(string $ability, callable $callback): self
    {
        $this->callbacks[$ability] = $callback;

        return $this;
    }

    /**
     * @param list<string> $abilities
     */
    public function resource(
        string $resourceName,
        string $modelClass,
        array $abilities = ['viewAny', 'view', 'create', 'update', 'delete']
    ): self {
        foreach ($abilities as $ability) {
            $this->defineResourceAbility($resourceName, $ability, $modelClass);
        }

        $this->registry->register($modelClass, $this->resolvePolicyClass($resourceName));

        return $this;
    }

    /**
     * @param string $ability
     */
    protected function defineResourceAbility(string $resourceName, string $ability, string $modelClass): void
    {
        $gate = $this;

        $this->define("{$resourceName}.{$ability}", function (UserInterface $user, mixed $model = null) use ($gate, $ability, $resourceName) {
            if ($ability === 'viewAny') {
                return $gate->check("{$resourceName}.viewAny", null);
            }

            if ($model === null) {
                return $gate->check("{$resourceName}.{$ability}", null);
            }

            return $gate->check("{$resourceName}.{$ability}", $model);
        });
    }

    protected function getPolicyForResource(mixed $resource): ?object
    {
        if ($resource === null) {
            return null;
        }

        $modelClass = is_object($resource) ? get_class($resource) : $resource;

        if (!$this->registry->hasPolicy($modelClass)) {
            return null;
        }

        return $this->registry->resolve($modelClass);
    }

    protected function resolveMethod(string $ability): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ability))));
    }

    protected function buildPermissionString(string $ability, mixed $resource): string
    {
        if ($resource === null) {
            return $ability;
        }

        $modelClass = is_object($resource) ? get_class($resource) : $resource;
        $modelName = $this->getShortClassName($modelClass);

        return strtolower($modelName) . '.' . $ability;
    }

    protected function getShortClassName(string $class): string
    {
        $parts = explode('\\', $class);

        return strtolower(end($parts));
    }

    protected function resolvePolicyClass(string $resourceName): string
    {
        $policyName = $resourceName . 'Policy';

        $namespace = 'App\\Policies\\' . $policyName;

        if (class_exists($namespace)) {
            return $namespace;
        }

        return $policyName;
    }

    public function disableBeforeCallbacks(): self
    {
        $this->beforeCallbacksDisabled = true;

        return $this;
    }

    public function enableBeforeCallbacks(): self
    {
        $this->beforeCallbacksDisabled = false;

        return $this;
    }

    public function getRegistry(): PolicyRegistry
    {
        return $this->registry;
    }
}
