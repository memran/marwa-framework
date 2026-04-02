<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use Marwa\Framework\Adapters\Event\ModulesBootstrapped;
use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Config\ModuleConfig;
use Marwa\Framework\Supports\Config;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\Module;
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
     *     commandConventions: list<string>
     * }|null
     */
    private ?array $moduleConfig = null;

    private ?ModuleRegistryInterface $registry = null;

    public function __construct(
        private Application $app,
        private Container $container,
        private Config $config
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
        $this->app->bootModuleServiceProviders();
        $this->registerModuleViews($this->registry);
        $this->loadModuleRoutes($this->registry);
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
        $registry = $this->registry();

        if ($registry === null) {
            return [];
        }

        $sources = [];

        foreach ($registry->all() as $module) {
            foreach ($this->resolveCommandPaths($module) as $path) {
                $sources[] = [
                    'path' => $path,
                ];
            }
        }

        return $sources;
    }

    private function registerModuleViews(ModuleRegistryInterface $registry): void
    {
        if (!$this->container->has(ViewAdapter::class)) {
            return;
        }

        $view = $this->container->get(ViewAdapter::class);

        foreach ($registry->all() as $module) {
            $viewsPath = $module->path('views');

            if (!is_string($viewsPath) || !is_dir($viewsPath)) {
                continue;
            }

            $view->addNamespace($this->moduleNamespace($module->slug()), $viewsPath);
        }
    }

    private function loadModuleRoutes(ModuleRegistryInterface $registry): void
    {
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
     *     commandConventions: list<string>
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
         *     commandConventions: list<string>
         * } $moduleConfig
         */
        $moduleConfig = array_replace_recursive(ModuleConfig::defaults($this->app), $this->config->getArray(ModuleConfig::KEY, []));
        $moduleConfig['paths'] = $this->normalizeStringList($moduleConfig['paths']);
        $moduleConfig['commandPaths'] = $this->normalizeStringList($moduleConfig['commandPaths']);
        $moduleConfig['commandConventions'] = $this->normalizeStringList($moduleConfig['commandConventions']);
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

    /**
     * @return list<string>
     */
    private function resolveCommandPaths(Module $module): array
    {
        $config = $this->moduleConfig();
        $paths = [];

        foreach ($config['commandPaths'] as $key) {
            $path = $module->path($key);

            if (is_string($path) && is_dir($path)) {
                $paths[] = $path;
            }
        }

        foreach ($config['commandConventions'] as $relativePath) {
            $path = $module->basePath() . DIRECTORY_SEPARATOR . trim($relativePath, DIRECTORY_SEPARATOR);

            if (is_dir($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
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
