<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\Framework\Application;
use Marwa\Framework\Config\ViewConfig as ViewConfigContract;
use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Supports\Config;
use Marwa\Router\Response;
use Marwa\View\Theme\{ThemeBootstrap, ThemeBuilder};
use Marwa\View\View;
use Marwa\View\ViewConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;

final class ViewAdapter
{
    protected View $engine;
    private ?ThemeBuilder $themeBuilder = null;

    public function __construct(private Application $app, private Config $config)
    {
        $this->config->loadIfExists(ViewConfigContract::KEY . '.php');
        $defaults = ViewConfigContract::defaults($this->app);

        $viewsPath = $this->config->getString(ViewConfigContract::KEY . '.viewsPath', $defaults['viewsPath']);
        $cachePath = $this->config->getString(ViewConfigContract::KEY . '.cachePath', $defaults['cachePath']);
        $this->ensureDirectory($viewsPath);
        $this->ensureDirectory($cachePath);

        $cacheEnabled = $this->config->getArray(ViewConfigContract::KEY . '.cache', $defaults['cache']);
        $isCacheEnabled = (bool) ($cacheEnabled['enabled'] ?? true);

        $finalCachePath = $isCacheEnabled ? $cachePath : false;

        $viewConfig = new ViewConfig(
            viewsPath: $viewsPath,
            cachePath: $finalCachePath,
            debug: $this->config->getBool(ViewConfigContract::KEY . '.debug', $defaults['debug']),
        );

        $this->createViewEngine($viewConfig);
    }

    /**
     * @return array<int, AbstractExtension>
     */
    private function loadExtensions(): array
    {
        $defaults = ViewConfigContract::defaults($this->app);
        $extensions = $this->config->getArray(ViewConfigContract::KEY . '.extensions', $defaults['extensions']);

        $loadedExtensions = [];

        foreach ($extensions as $extensionClass) {
            if (!is_string($extensionClass)) {
                continue;
            }

            if (!class_exists($extensionClass)) {
                $this->logger()->warning('View extension class does not exist.', [
                    'class' => $extensionClass,
                ]);
                continue;
            }

            $extension = new $extensionClass();

            if (!$extension instanceof AbstractExtension) {
                $this->logger()->warning('View extension must extend Twig\Extension\AbstractExtension.', [
                    'class' => $extensionClass,
                ]);
                continue;
            }

            if ($extension instanceof ViewExtensionInterface) {
                $extension->register();
            }

            $loadedExtensions[] = $extension;
        }

        return $loadedExtensions;
    }

    public function createViewEngine(ViewConfig $config): View
    {
        $this->themeBuilder = $this->getThemeBuilder();
        $extensions = $this->loadExtensions();

        $this->engine = new View(
            config: $config,
            extensions: $extensions,
            themeBuilder: $this->themeBuilder
        );

        return $this->engine;
    }

    public function getView(): View
    {
        return $this->engine;
    }

    public function engine(): View
    {
        return $this->engine;
    }

    public function themeBuilder(): ?ThemeBuilder
    {
        return $this->themeBuilder;
    }

    public function currentTheme(): ?string
    {
        return $this->themeBuilder?->current();
    }

    public function selectedTheme(): ?string
    {
        return $this->themeBuilder?->selected();
    }

    public function useTheme(string $themeName): void
    {
        $this->themeBuilder?->useTheme($themeName);
    }

    public function share(string $namespace, mixed $value): void
    {
        $this->engine->share($namespace, $value);
    }

    public function addNamespace(string $namespace, string $path): void
    {
        $this->engine->addNamespace($namespace, $path);
    }

    public function exists(string $template): bool
    {
        $twig = $this->twig();
        $loader = $twig->getLoader();

        return $loader->exists($template);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render(string $tplname, array $params = []): ResponseInterface
    {
        return Response::html($this->engine->render($tplname, $params));
    }

    protected function getThemeBuilder(): ?ThemeBuilder
    {
        $defaults = ViewConfigContract::defaults($this->app);
        $themesBaseDir = $this->config->getString(ViewConfigContract::KEY . '.themePath', $defaults['themePath']);
        $this->ensureDirectory($themesBaseDir);
        $activeTheme = $this->config->getString(ViewConfigContract::KEY . '.activeTheme', $defaults['activeTheme']);
        $fallbackTheme = $this->config->getString(ViewConfigContract::KEY . '.fallbackTheme', $defaults['fallbackTheme']);

        $selectedTheme = $this->hasThemeManifest($themesBaseDir, $activeTheme)
            ? $activeTheme
            : ($this->hasThemeManifest($themesBaseDir, $fallbackTheme) ? $fallbackTheme : '');

        if ($selectedTheme === '') {
            return null;
        }

        return ThemeBootstrap::initFromDirectory(
            themesBaseDir: $themesBaseDir,
            defaultTheme: $selectedTheme
        );
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0775, true);
    }

    private function hasThemeManifest(string $themesBaseDir, string $defaultTheme): bool
    {
        $themePath = $themesBaseDir . DIRECTORY_SEPARATOR . $defaultTheme;

        return is_file($themePath . DIRECTORY_SEPARATOR . 'manifest.php')
            || is_file($themePath . DIRECTORY_SEPARATOR . 'manifest.json');
    }

    private function twig(): \Twig\Environment
    {
        $reflection = new \ReflectionObject($this->engine);
        $property = $reflection->getProperty('twig');

        /** @var \Twig\Environment $twig */
        $twig = $property->getValue($this->engine);

        return $twig;
    }

    private function logger(): LoggerInterface
    {
        return $this->app->container()->get(LoggerInterface::class);
    }
}
