<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use Marwa\Framework\Adapters\Event\ModulesBootstrapped;
use Marwa\Framework\Application;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Exceptions\ModuleDependencyException;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View as FrameworkView;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\ModulesServiceProvider;

final class ModuleBootstrapper
{
    private bool $booted = false;

    /**
     * @var array{
     *     enabled: bool,
     *     paths: list<string>,
     *     cache: string,
     *     forceRefresh: bool,
     *     commandPaths: list<string>,
     *     commandConventions: list<string>,
     *     migrationsPath: list<string>,
     *     seedersPath: list<string>
     * }|null
     */
    private ?array $moduleConfig = null;

    private ?ModuleRegistryInterface $registry = null;

    /**
     * @var list<array{path:string,optional?:bool}>|null
     */
    private ?array $consoleDiscoverySources = null;

    /**
     * @var list<string>|null
     */
    private ?array $migrationPaths = null;

    /**
     * @var list<string>|null
     */
    private ?array $seederPaths = null;

    public function __construct(
        private Application $app,
        private Container $container,
        private Config $config,
        private ModuleManifestReader $manifestReader = new ModuleManifestReader(),
        private ModulePathResolver $pathResolver = new ModulePathResolver()
    ) {}

    public function bootstrap(): void
    {
        if ($this->booted || !class_exists(ModulesServiceProvider::class)) {
            $this->booted = true;

            return;
        }

        $moduleConfig = $this->moduleConfig();

        if (!$moduleConfig['enabled']) {
            $this->booted = true;

            return;
        }

        $provider = new ModulesServiceProvider(
            $moduleConfig['paths'],
            $moduleConfig['cache'],
            $moduleConfig['forceRefresh']
        );

        $provider->register($this->app);
        $this->registry = $this->resolveRegistry();
        $this->assertModuleDependencies($this->registry);
        $this->app->bootModuleServiceProviders();
        $this->registerModuleListeners($this->registry);
        $this->dispatchModuleEvents($this->registry);
        $this->loadModuleConfigs($this->registry);
        $this->shareMainMenu();

        if (!Runtime::isConsole()) {
            $this->registerModuleViews($this->registry);
            $this->loadModuleRoutes($this->registry);
        }

        $this->app->dispatch(new ModulesBootstrapped(modules: array_keys($this->registry->all())));

        $this->booted = true;
    }

    public function registry(): ?ModuleRegistryInterface
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

        return $this->registry;
    }

    /**
     * @return list<array{path:string,optional?:bool}>
     */
    public function consoleDiscoverySources(): array
    {
        return $this->resolvePaths(
            'consoleDiscoverySources',
            fn ($module, $config) => $this->pathResolver->resolveCommandPaths($module, $config),
            true
        );
    }

    /**
     * @return list<string>
     */
    public function migrationPaths(): array
    {
        return $this->resolvePaths(
            'migrationPaths',
            fn ($module, $config) => $this->pathResolver->resolveMigrationPaths($module, $config)
        );
    }

    /**
     * @return list<string>
     */
    public function seederPaths(): array
    {
        return $this->resolvePaths(
            'seederPaths',
            fn ($module, $config) => $this->pathResolver->resolveSeederPaths($module, $config)
        );
    }

    /**
     * @param string $cacheKey
     * @param callable $resolver
     * @param bool $wrapAsArray
     * @return list<string|array{path: string}>
     */
    private function resolvePaths(string $cacheKey, callable $resolver, bool $wrapAsArray = false): array
    {
        if ($this->{$cacheKey} !== null) {
            return $this->{$cacheKey};
        }

        $registry = $this->registry();
        if ($registry === null) {
            $this->{$cacheKey} = [];
            return [];
        }

        $paths = [];
        $moduleConfig = $this->moduleConfig();

        foreach ($registry->all() as $module) {
            foreach ($resolver($module, $moduleConfig) as $path) {
                if ($wrapAsArray && is_string($path)) {
                    $paths[] = ['path' => $path];
                } else {
                    $paths[] = $path;
                }
            }
        }

        $this->{$cacheKey} = array_values(array_unique($paths, SORT_REGULAR));

        return $this->{$cacheKey};
    }

    private function loadModuleConfigs(ModuleRegistryInterface $registry): void
    {
        foreach ($registry->all() as $module) {
            $this->loadModuleConfig($module);
        }
    }

    private function loadModuleConfig(\Marwa\Module\Module $module): void
    {
        $slug = $module->slug();
        $basePath = $module->basePath();
        $configDir = $basePath . DIRECTORY_SEPARATOR . 'config';

        if (!is_dir($configDir)) {
            return;
        }

        $configKey = 'modules.' . $slug;

        foreach (glob($configDir . '/*.php') as $file) {
            $filename = basename($file, '.php');

            $config = require $file;

            if (is_array($config)) {
                $this->config->set($configKey . '.' . $filename, $config);
            }
        }

        $manifest = $module->manifest();
        $configArray = $manifest['config'] ?? null;
        if (is_array($configArray)) {
            foreach ($configArray as $key => $value) {
                $this->config->set($configKey . '.' . $key, $value);
            }
        }
    }

    /**
     * Register module listeners from manifest.
     */
    private function registerModuleListeners(ModuleRegistryInterface $registry): void
    {
        if (!$this->container->has(\Psr\EventDispatcher\EventDispatcherInterface::class)) {
            return;
        }

        $eventDispatcher = $this->container->get(\Psr\EventDispatcher\EventDispatcherInterface::class);

        foreach ($registry->all() as $module) {
            $manifest = $module->manifest();
            $listeners = $manifest['listeners'] ?? null;

            if (!is_array($listeners)) {
                continue;
            }

            foreach ($listeners as $eventName => $listenerClasses) {
                if (!is_array($listenerClasses)) {
                    $listenerClasses = [$listenerClasses];
                }

                /** @var string|mixed $listenerClass */
                foreach ($listenerClasses as $listenerClass) {
                    if (is_string($listenerClass) && class_exists($listenerClass)) {
                        $eventDispatcher->listen($eventName, new $listenerClass());
                    }
                }
            }
        }
    }

    /**
     * Dispatch individual module events during bootstrap.
     */
    private function dispatchModuleEvents(ModuleRegistryInterface $registry): void
    {
        if (!$this->container->has(\Psr\EventDispatcher\EventDispatcherInterface::class)) {
            return;
        }

        $eventDispatcher = $this->container->get(\Psr\EventDispatcher\EventDispatcherInterface::class);

        foreach ($registry->all() as $module) {
            $slug = $module->slug();
            $manifest = $module->manifest();
            $nameValue = $manifest['name'] ?? $slug;
            $name = is_string($nameValue) ? $nameValue : $slug;

            $eventDispatcher->dispatch(
                new \Marwa\Framework\Adapters\Event\ModuleLoaded(slug: $slug, name: $name)
            );
        }
    }

    private function registerModuleViews(ModuleRegistryInterface $registry): void
    {
        if (!$this->container->has(FrameworkView::class)) {
            return;
        }

        $view = $this->container->get(FrameworkView::class);

        if ($this->isDebug()) {
            $this->clearTwigCacheIfDev();
        }

        foreach ($registry->all() as $module) {
            $namespace = $this->moduleNamespace($module->slug());
            $viewPaths = $this->resolveModuleViewsPaths($module);

            if (empty($viewPaths)) {
                if ($this->isDebug()) {
                    error_log("[ModuleViews] {$namespace}: no views path found");
                }
                continue;
            }

            foreach ($viewPaths as $path) {
                $view->addNamespace($namespace, $path);
            }

            if ($this->isDebug()) {
                error_log("[ModuleViews] {$namespace}: registered " . count($viewPaths) . " path(s)");
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolveModuleViewsPaths(\Marwa\Module\Module $module): array
    {
        $paths = [];
        $basePath = $module->basePath();

        $conventionalPath = $basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
        if (is_dir($conventionalPath)) {
            $paths[] = $conventionalPath;
        }

        $viewPathValue = $module->path('views');
        if ($viewPathValue !== null && is_dir($viewPathValue)) {
            $paths[] = $viewPathValue;
        }

        return array_values(array_unique($paths));
    }

    private function isDebug(): bool
    {
        static $debug = null;

        if ($debug === null) {
            $debug = $this->config->getBool('app.debug', false);
        }

        return $debug;
    }

    private function clearTwigCacheIfDev(): void
    {
        $cachePath = $this->config->getString('view.cachePath');

        if ($cachePath !== '' && is_dir($cachePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    @unlink($file->getPathname());
                }
            }
        }
    }

    private function loadModuleRoutes(ModuleRegistryInterface $registry): void
    {
        if ($this->app->areModuleRoutesLoaded()) {
            return;
        }

        $routeCache = BootstrapConfig::defaults($this->app)['routeCache'];

        if (is_file($routeCache)) {
            return;
        }

        foreach ($registry->all() as $module) {
            foreach (['http', 'api'] as $channel) {
                $routeFile = $module->routeFile($channel);

                if (is_string($routeFile) && is_file($routeFile)) {
                    require $routeFile;
                }
            }
        }

        $this->app->setModuleRoutesLoaded(true);
    }

    private function moduleNamespace(string $slug): string
    {
        $namespace = preg_replace('/[^A-Za-z0-9_]/', '_', $slug) ?: 'Module';

        if (preg_match('/^[A-Za-z]/', $namespace) !== 1) {
            $namespace = 'Module_' . $namespace;
        }

        return $namespace;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     paths: list<string>,
     *     cache: string,
     *     forceRefresh: bool,
     *     commandPaths: list<string>,
     *     commandConventions: list<string>,
     *     migrationsPath: list<string>,
     *     seedersPath: list<string>
     * }
     */
    private function moduleConfig(): array
    {
        if ($this->moduleConfig !== null) {
            return $this->moduleConfig;
        }

        $this->config->loadIfExists(ModuleConfig::KEY . '.php');

        /** @var array{
         *     enabled: bool,
         *     paths: list<string>,
         *     cache: string,
         *     forceRefresh: bool,
         *     commandPaths: list<string>,
         *     commandConventions: list<string>,
         *     migrationsPath: list<string>,
         *     seedersPath: list<string>
         * } $moduleConfig
         */
        $moduleConfig = array_replace_recursive(ModuleConfig::defaults($this->app), $this->config->getArray(ModuleConfig::KEY, []));
        $moduleConfig['paths'] = $this->normalizeStringList($moduleConfig['paths']);
        $moduleConfig['commandPaths'] = $this->normalizeStringList($moduleConfig['commandPaths']);
        $moduleConfig['commandConventions'] = $this->normalizeStringList($moduleConfig['commandConventions']);
        $moduleConfig['migrationsPath'] = $this->normalizeStringList($moduleConfig['migrationsPath']);
        $moduleConfig['seedersPath'] = $this->normalizeStringList($moduleConfig['seedersPath']);
        $moduleConfig['forceRefresh'] = (bool) $moduleConfig['forceRefresh'];
        $moduleConfig['enabled'] = (bool) $moduleConfig['enabled'];
        $moduleConfig['cache'] = $moduleConfig['cache'];

        $this->moduleConfig = $moduleConfig;

        return $this->moduleConfig;
    }

    private function resolveRegistry(): ModuleRegistryInterface
    {
        $services = [
            ModuleRegistryInterface::class,
            \Marwa\Module\ModuleRegistry::class,
        ];

        foreach ($services as $service) {
            if ($this->container->has($service)) {
                /** @var ModuleRegistryInterface $registry */
                $registry = $this->container->get($service);

                return $registry;
            }
        }

        throw new \RuntimeException('marwa-module did not register a module registry instance.');
    }

    private function shareMainMenu(): void
    {
        if (
            !$this->container->has(FrameworkView::class)
            || !$this->container->has(MenuRegistry::class)
        ) {
            return;
        }

        $view = $this->container->get(FrameworkView::class);
        $menu = $this->container->get(MenuRegistry::class);

        $view->share('mainMenu', $menu->tree());
    }

    private function assertModuleDependencies(ModuleRegistryInterface $registry): void
    {
        $registeredModules = [];

        foreach ($registry->all() as $registeredModule) {
            $registeredModules[strtolower($registeredModule->slug())] = true;
        }

        foreach ($registry->all() as $module) {
            $requiredModules = $this->manifestReader->getRequiredModules($module);

            if ($requiredModules === []) {
                continue;
            }

            $missingModules = array_values(array_filter(
                $requiredModules,
                static fn (string $slug): bool => !isset($registeredModules[strtolower($slug)])
            ));

            if ($missingModules === []) {
                continue;
            }

            throw new ModuleDependencyException(sprintf(
                'Module [%s] requires missing module(s): %s.',
                $module->slug(),
                implode(', ', $missingModules)
            ));
        }
    }

    /**
     * @param array<mixed> $values
     * @return list<string>
     */
    private function normalizeStringList(array $values): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null,
                $values
            ),
            static fn (?string $value): bool => $value !== null
        ));
    }
}
