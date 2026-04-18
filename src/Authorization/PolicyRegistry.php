<?php

declare(strict_types=1);

namespace Marwa\Framework\Authorization;

use Marwa\Framework\Exceptions\AuthorizationException;

class PolicyRegistry
{
    /**
     * @var array<string, string>
     */
    private array $policies = [];

    /**
     * @var array<string, string>
     */
    private array $modelAliases = [];

    public function register(string $modelClass, string $policyClass): self
    {
        $this->policies[$modelClass] = $policyClass;

        return $this;
    }

    public function registerAlias(string $alias, string $modelClass): self
    {
        $this->modelAliases[$alias] = $modelClass;

        return $this;
    }

    public function getPolicy(string $modelClass): ?string
    {
        $resolvedClass = $this->resolveClass($modelClass);

        return $this->policies[$resolvedClass] ?? null;
    }

    public function hasPolicy(string $modelClass): bool
    {
        return $this->getPolicy($modelClass) !== null;
    }

    public function resolve(string $modelClass): object
    {
        $resolvedClass = $this->resolveClass($modelClass);
        $policyClass = $this->getPolicy($resolvedClass);

        if ($policyClass === null) {
            throw new AuthorizationException(
                "No policy registered for [{$resolvedClass}]",
                '',
                $modelClass
            );
        }

        return new $policyClass();
    }

    private function resolveClass(string $class): string
    {
        if (isset($this->modelAliases[$class])) {
            return $this->modelAliases[$class];
        }

        return $class;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->policies;
    }

    /**
     * @param array<string, string> $policies
     */
    public function loadFromConfig(array $policies): self
    {
        foreach ($policies as $model => $policy) {
            $this->register($model, $policy);
        }

        return $this;
    }
}
