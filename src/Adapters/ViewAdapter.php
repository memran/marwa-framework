<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\Framework\Application;
use Marwa\Framework\Config\ViewConfig as ViewConfigContract;
use Marwa\Framework\Supports\Config;
use Marwa\Router\Response;
use Marwa\View\Theme\{ThemeBootstrap, ThemeBuilder};
use Marwa\View\View;
use Marwa\View\ViewConfig;
use Psr\Http\Message\ResponseInterface;

final class ViewAdapter
{
    protected View $engine;

    public function __construct(private Application $app, private Config $config)
    {
        $this->config->loadIfExists(ViewConfigContract::KEY . '.php');
        $defaults = ViewConfigContract::defaults($this->app);

        $viewsPath = $this->config->getString(ViewConfigContract::KEY . '.viewsPath', $defaults['viewsPath']);
        $cachePath = $this->config->getString(ViewConfigContract::KEY . '.cachePath', $defaults['cachePath']);
        $this->ensureDirectory($viewsPath);
        $this->ensureDirectory($cachePath);

        $config = new ViewConfig(
            viewsPath: $viewsPath,
            cachePath: $cachePath,
            debug: $this->config->getBool(ViewConfigContract::KEY . '.debug', $defaults['debug']),
        );

        $this->createViewEngine($config);
    }
    public function createViewEngine(ViewConfig $config): View
    {
        $this->engine = new View(
            config: $config,
            extensions: [],
            themeBuilder: $this->getThemeBuilder()
        );
        return $this->engine;
    }
    public function getView(): View
    {
        return $this->engine;
    }

    public function addNamespace(string $namespace, string $path): void
    {
        $this->engine->addNamespace($namespace, $path);
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
        $viewsPath = $this->config->getString(ViewConfigContract::KEY . '.viewsPath', $defaults['viewsPath']);
        $themesBaseDir = $viewsPath . DIRECTORY_SEPARATOR . 'themes';
        $this->ensureDirectory($themesBaseDir);
        $defaultTheme = $this->config->getString(ViewConfigContract::KEY . '.defaultTheme', $defaults['defaultTheme']);

        if (!$this->hasThemeManifest($themesBaseDir, $defaultTheme)) {
            return null;
        }

        return ThemeBootstrap::initFromDirectory(
            themesBaseDir: $themesBaseDir,
            defaultTheme: $defaultTheme
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
}
