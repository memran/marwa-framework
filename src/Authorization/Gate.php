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
     * Register a policy for a model class with auto-discovery.
     *
     * Resolution order:
     * 1. Module Policies folder (modules/{Module}/Policies/{Model}Policy.php)
     * 2. Global config (permissions.policies)
     * 3. Registered via Gate::policy() before
     *
     * Usage:
     *   gate()->policy(User::class);           // Auto-discover
     *   gate()->policy(User::class, CustomPolicy::class); // Manual
     */
    public function policy(string $modelClass, string|callable|null $policyClass = null): mixed
    {
        // Register manually if provided
        if ($policyClass !== null) {
            if (is_callable($policyClass)) {
                $this->registry->register($modelClass, $policyClass);
            } elseif (is_string($policyClass) && class_exists($policyClass)) {
                $this->registry->register($modelClass, $policyClass);
            }
            return $this;
        }

        // Auto-discover: first try module, then config, then registry
        if ($this->registry->hasPolicy($modelClass)) {
            return $this->registry->resolve($modelClass);
        }

        // Try module policy auto-discovery
        $discoveredPolicy = $this->resolvePolicyFromModule($modelClass);
        if ($discoveredPolicy !== null) {
            $this->registry->register($modelClass, $discoveredPolicy);
            return $this->registry->resolve($modelClass);
        }

        // Try global config fallback
        $configPolicies = config('permissions.policies', []);
        if (isset($configPolicies[$modelClass])) {
            $policyFromConfig = $configPolicies[$modelClass];
            if (is_string($policyFromConfig) && class_exists($policyFromConfig)) {
                $this->registry->register($modelClass, $policyFromConfig);
                return $this->registry->resolve($modelClass);
            }
        }

        // Return null if not found - caller can handle
        return null;
    }

    /**
     * Resolve policy from module Policies folder using module registry.
     */
    private function resolvePolicyFromModule(string $modelClass): ?string
    {
        $modelName = (new \ReflectionClass($modelClass))->getShortName();
        $policyClassName = $modelName . 'Policy';

        // Get module slug from model namespace
        $moduleSlug = $this->extractModuleSlug($modelClass);

        // Try to find module via application module registry
        $module = $this->findModule($moduleSlug);

        if ($module === null) {
            return null;
        }

        // Get custom policy path from manifest if configured
        $policiesPath = $module->path('policies') ?? 'Policies';

        // Build policy path using module's basePath()
        $policyPath = $module->basePath() . DIRECTORY_SEPARATOR . $policiesPath . DIRECTORY_SEPARATOR . $policyClassName . '.php';

        if (file_exists($policyPath)) {
            // Try to construct namespace from module
            $moduleName = ucfirst($moduleSlug);
            $policyNamespace = "App\\Modules\\{$moduleName}\\Policies\\{$policyClassName}";

            if (class_exists($policyNamespace)) {
                return $policyNamespace;
            }
        }

        return null;
    }

    /**
     * Find module by slug from application module registry.
     */
    private function findModule(?string $slug): ?object
    {
        if ($slug === null) {
            return null;
        }

        $modules = $this->getModuleRegistry();

        if (isset($modules[$slug])) {
            return $modules[$slug];
        }

        return null;
    }

    /**
     * Get module registry from application.
     *
     * @return array<string, object>
     */
    private function getModuleRegistry(): array
    {
        static $modules = null;

        if ($modules === null) {
            $app = $this->getApplication();
            if ($app !== null && method_exists($app, 'modules')) {
                $modules = $app->modules();
            }
        }

        return $modules ?? [];
    }

    /**
     * Get Application instance.
     */
    private function getApplication(): ?object
    {
        static $app = null;

        if ($app === null) {
            global $app;
        }

        return $app;
    }

    /**
     * Extract module slug from model class namespace.
     */
    private function extractModuleSlug(string $modelClass): ?string
    {
        $matches = [];

        // App\Modules\Users\Models\User -> users
        if (preg_match('/\\\\Modules\\\\([A-Za-z]+)\\\\Models\\\\/', $modelClass, $matches)) {
            return strtolower($matches[1]);
        }

        // App\Models\User -> check for app/Policies
        if (str_starts_with($modelClass, 'App\\Models\\')) {
            $app = $this->getApplication();
            if ($app !== null) {
                $modelName = (new \ReflectionClass($modelClass))->getShortName();
                $globalPolicyPath = base_path('app' . DIRECTORY_SEPARATOR . 'Policies' . DIRECTORY_SEPARATOR . $modelName . 'Policy.php');
                if (file_exists($globalPolicyPath)) {
                    return 'app';
                }
            }
        }

        return null;
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
