<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\View\View;
use Marwa\View\ViewConfig;
use Marwa\Framework\Facades\Config;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Marwa\View\Extension\{AssetExtension, TextExtension, DateExtension, UrlExtension};
use Marwa\View\Theme\{ThemeBootstrap, ThemeBuilder}; // Ensure this class exists in your project

final class ViewAdapter
{
    protected View $engine;

    public function __construct()
    {
        Config::load('view.php');
        $config = new ViewConfig(
            viewsPath: Config::getString('view.viewsPath'),
            cachePath: Config::getString('view.cachePath'),
            debug: Config::getBool('view.debug'),
        );

        $this->createViewEngine($config);
        // Render a view
        //echo $view->render('home/index', ['title' => 'Welcome']);
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

    public function render(string $tplname, array $params = []): ResponseInterface
    {
        return Response::html($this->engine->render($tplname, $params));
    }

    protected function getThemeBuilder(): ThemeBuilder
    {
        // 1. Build ThemeBuilder automatically
        $themeBuilder = ThemeBootstrap::initFromDirectory(
            themesBaseDir: Config::getString('view.viewsPath') . DIRECTORY_SEPARATOR . 'themes',
            defaultTheme: Config::getString('view.defaultTheme')
        );
        return $themeBuilder;
    }
}
