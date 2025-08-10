<?php

declare(strict_types=1);

namespace Marwa\App\Core;

use Marwa\App\Facades\{Facade, App, Response, Router};
use Marwa\App\Core\Container;
use Marwa\App\Routes\RouteDirectoryLoader;

final class Application
{

    private ?string $path = null;

    private ?Container $container = null;

    /**
     * 
     */
    protected ?string $renderTime = null;

    /**
     * [public description] get globally instance of self object
     * @var self object
     */
    public static $instance;
    /**
     * Application constructor.
     *
     * @param string|null $path Base path for the application, defaults to the directory of this file.
     */
    public function __construct(string $path)
    {
        //set base path
        if ($path === null) {
            $this->path = dirname(__FILE__, 4);
        } else {
            $this->path = rtrim($path, '/\\');
        }

        $this->setBasePath($this->path);

        //boot configuration and others
        $this->bootstrap();
    }

    private function setBasePath(string $path): void
    {
        //////////APPLICATION PATH SET/////////////////////////////////////
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', rtrim($path, '/\\'));
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', BASE_PATH . DS . 'app');
        }
        if (!defined('CONFIG_PATH')) {
            define('CONFIG_PATH', BASE_PATH . DS . 'config');
        }
        if (!defined('ROUTES_PATH')) {
            define('ROUTES_PATH', BASE_PATH . DS . 'routes');
        }
        if (!defined('STORAGE_PATH')) {
            define('STORAGE_PATH', BASE_PATH . DS . 'storage');
        }
        if (!defined('PUBLIC_PATH')) {
            define('PUBLIC_PATH', BASE_PATH . DS . 'public');
        }
        if (!defined('RESOURCE_PATH')) {
            define('RESOURCE_PATH', BASE_PATH . DS . 'resources');
        }
        //////////////////////  INTERNAL USAGE PATH //////////////////////////////
        if (!defined('LOG_PATH')) {
            define('LOG_PATH', STORAGE_PATH . DS . 'logs');
        }
        if (!defined('CACHE_PATH')) {
            define('CACHE_PATH', STORAGE_PATH . DS . 'cache');
        }

        if (!defined('CONFIG_CACHE_PATH')) {
            define('CONFIG_CACHE_PATH', STORAGE_PATH . '/cache/config.cache.php');
        }
        if (!defined('BASE_URL')) {
            define('BASE_URL', '/');
        }
        if (!defined('PUBLIC_STORAGE')) {
            define('PUBLIC_STORAGE', PUBLIC_PATH . DS . 'storage');
        }
        if (!defined('VIEWS_PATH')) {
            define('VIEWS_PATH', RESOURCE_PATH . DS . 'views');
        }
        if (!defined('ASSETS_PATH')) {
            define('ASSETS_PATH', RESOURCE_PATH . DS . 'assets');
        }
        if (!defined('LANG_PATH')) {
            define('LANG_PATH', RESOURCE_PATH . DS . 'lang');
        }

        if (!defined('MIDDLEWARE_PATH')) {
            define('MIDDLEWARE_PATH', APP_PATH . '/Middleware');
        }
        if (!defined('CONTROLLERS_PATH')) {
            define('CONTROLLERS_PATH', APP_PATH . '/Controllers');
        }
        if (!defined('MODELS_PATH')) {
            define('MODELS_PATH', APP_PATH . '/Models');
        }
        if (!defined('VENDOR_PATH')) {
            define('VENDOR_PATH', BASE_PATH . '/vendor');
        }
        if (!defined('HELPERS_PATH')) {
            define('HELPERS_PATH', APP_PATH . '/Helpers');
        }

        if (!defined('VENDOR_PATH')) {
            define('VENDOR_PATH', BASE_PATH . '/vendor');
        }
    }
    /**
     * 
     */
    private function bootContainer(): void
    {
        $this->container = Container::getInstance();

        // ... register services
        Facade::setContainer($this->container);
    }
    /**
     * 
     */
    private function bootstrap(): void
    {
        //Boot the container
        $this->bootContainer();

        // dd($this->container);
        App::register('Marwa\App\ServiceProvider\AppBootableServiceProvider');
        App::register('Marwa\App\ServiceProvider\RouteServiceProvider');
        //Loading All service providers and register
        App::loadProviders(config('app.providers'));
    }


    /**
     * 
     */
    public function run(): void
    {

        if (env('APP_ENV') === 'development') {
            $this->calcRenderTime();
        }
        // if (app('router') === Router::getRouter()) {
        //     dd("Equals");
        // }
        $loader = new RouteDirectoryLoader(ROUTES_PATH);
        $loader->load();
        //dd("Printing Configuration", app('config')->all(), app('config')->get('app.env'), app('config')->get('app.debug'));
        //dd('Routing Dispatch', app('router'));

        app('emitter')->emit(app('router')->dispatch());

        exit(0);
    }


    /**
     * [renderTime description] it will return application render time
     * @return [type] [description]
     */
    private function calcRenderTime()
    {
        $start = START_APP;
        $end = microtime(true);
        //$renderTime = ($end - $start);
        $this->renderTime = number_format(($end - $start), 4) . " seconds";
    }
}
