<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Application;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Module\Contracts\ModuleRegistryInterface;

/**
 * Loads route definitions from the app and module route files, and decides
 * whether the compiled route cache replaces them.
 */
final class RouteFileLoader
{
    private const MODULE_CHANNELS = ['http', 'api'];

    public function __construct(private Application $app) {}

    /**
     * The compiled route cache is production-only, like the config cache, so a
     * stale cache never hides route changes during development.
     */
    public function shouldUseCache(): bool
    {
        return BootstrapConfig::shouldCache()
            && is_file(BootstrapConfig::defaults($this->app)['routeCache']);
    }

    public function loadAppRoutes(): void
    {
        foreach (['routes/web.php', 'routes/api.php'] as $file) {
            $path = $this->app->basePath($file);

            if (is_file($path)) {
                require $path;
            }
        }
    }

    public function loadModuleRoutes(?ModuleRegistryInterface $registry): void
    {
        if ($registry === null || $this->app->areModuleRoutesLoaded()) {
            return;
        }

        foreach ($registry->all() as $module) {
            foreach (self::MODULE_CHANNELS as $channel) {
                $routeFile = $module->routeFile($channel);

                if (is_string($routeFile) && is_file($routeFile)) {
                    require $routeFile;
                }
            }
        }

        $this->app->setModuleRoutesLoaded(true);
    }
}
