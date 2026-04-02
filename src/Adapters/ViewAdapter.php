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

        $cachePath = $this->config->getString(ViewConfigContract::KEY . '.cachePath', $defaults['cachePath']);
        $this->ensureDirectory($cachePath);

        $config = new ViewConfig(
            viewsPath: $this->config->getString(ViewConfigContract::KEY . '.viewsPath', $defaults['viewsPath']),
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

    /**
     * @param array<string, mixed> $params
     */
    public function render(string $tplname, array $params = []): ResponseInterface
    {
        return Response::html($this->engine->render($tplname, $params));
    }

    protected function getThemeBuilder(): ThemeBuilder
    {
        $defaults = ViewConfigContract::defaults($this->app);
        $viewsPath = $this->config->getString(ViewConfigContract::KEY . '.viewsPath', $defaults['viewsPath']);
        $themesBaseDir = $viewsPath . DIRECTORY_SEPARATOR . 'themes';
        $this->ensureDirectory($themesBaseDir);

        $themeBuilder = ThemeBootstrap::initFromDirectory(
            themesBaseDir: $themesBaseDir,
            defaultTheme: $this->config->getString(ViewConfigContract::KEY . '.defaultTheme', $defaults['defaultTheme'])
        );
        return $themeBuilder;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0775, true);
    }
}
