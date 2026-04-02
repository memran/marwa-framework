<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\Framework\Facades\Config;
use Marwa\Router\Response;
use Marwa\View\Theme\{ThemeBootstrap, ThemeBuilder};
use Marwa\View\View;
use Marwa\View\ViewConfig;
use Psr\Http\Message\ResponseInterface;

final class ViewAdapter
{
    protected View $engine;

    public function __construct()
    {
        Config::loadIfExists('view.php');
        $cachePath = Config::getString('view.cachePath', storage_path('cache/views'));
        $this->ensureDirectory($cachePath);

        $config = new ViewConfig(
            viewsPath: Config::getString('view.viewsPath', resources_path('views')),
            cachePath: $cachePath,
            debug: Config::getBool('view.debug', (bool) env('APP_DEBUG', false)),
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
        $viewsPath = Config::getString('view.viewsPath', resources_path('views'));
        $themesBaseDir = $viewsPath . DIRECTORY_SEPARATOR . 'themes';
        $this->ensureDirectory($themesBaseDir);

        $themeBuilder = ThemeBootstrap::initFromDirectory(
            themesBaseDir: $themesBaseDir,
            defaultTheme: Config::getString('view.defaultTheme', 'default')
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
