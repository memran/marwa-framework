<?php

declare(strict_types=1);

namespace Marwa\App\Core;

use Marwa\App\Configs\Config;
use Marwa\App\Facades\Facade;
use Marwa\App\Http\Response\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;


final class Application
{
    private string $configPath;
    /**
     * Application constructor.
     *
     * @param string $configPath Path to the configuration file.
     */
    private string $basePath;
    /**
     * Base path for the application, defaults to the directory of this file.
     */
    private ?string $path = null;

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
        $this->bootConfig();
        //$this->bootstrapErrorHandler();
    }

    private function setBasePath(string $path): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', rtrim($path, '/\\'));
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', BASE_PATH . '/app');
        }
        if (!defined('CONFIG_PATH')) {
            define('CONFIG_PATH', BASE_PATH . '/config');
        }
        if (!defined('STORAGE_PATH')) {
            define('STORAGE_PATH', BASE_PATH . '/storage');
        }
        if (!defined('PUBLIC_PATH')) {
            define('PUBLIC_PATH', BASE_PATH . '/public');
        }
        if (!defined('RESOURCE_PATH')) {
            define('RESOURCE_PATH', BASE_PATH . '/resources');
        }
        if (!defined('LOG_PATH')) {
            define('LOG_PATH', STORAGE_PATH . '/logs');
        }
        if (!defined('CACHE_PATH')) {
            define('CACHE_PATH', STORAGE_PATH . '/cache');
        }

        if (!defined('CONFIG_CACHE_PATH')) {
            define('CONFIG_CACHE_PATH', STORAGE_PATH . '/cache/config.cache.php');
        }
        if (!defined('BASE_URL')) {
            define('BASE_URL', '/');
        }
        if (!defined('PUBLIC_STORAGE')) {
            define('PUBLIC_STORAGE', PUBLIC_PATH . '/storage');
        }
        if (!defined('VIEWS_PATH')) {
            define('VIEWS_PATH', RESOURCE_PATH . '/views');
        }
        if (!defined('ASSETS_PATH')) {
            define('ASSETS_PATH', RESOURCE_PATH . '/assets');
        }
        if (!defined('LANG_PATH')) {
            define('LANG_PATH', RESOURCE_PATH . '/lang');
        }
        if (!defined('ROUTES_PATH')) {
            define('ROUTES_PATH', APP_PATH . '/Routes');
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
    private function bootConfig(): void
    {
        // ... register services
        Facade::setContainer(app());

        $config = new Config(BASE_PATH, CONFIG_PATH);

        if (env('APP_ENV') === "production")
            $config->setAutoCache(true);

        $config->load();

        app()->singleton('config', $config);
        //enable debugging.....
        $this->enableDebug();

        //loading and adding providers to service containers
        if (!is_null($config->get('app.providers')))
            app()->loadProviders($config->get('app.providers'));

        //dd("Printing Configuration", $config->all(), $config->get('env'), $config->get('app.debug'));


    }
    /**
     * 
     */
    public function run(): void
    {

        $resp = new ResponseFactory();

        $this->calcRenderTime();
        //dd('Running Application Succesfully', app('config')->all(), $this->renderTime);

        $response = $response = $resp->json(['status' => 'ok'], 201, ['X-Response-Time' => $this->renderTime]);

        $emitter = new SapiEmitter();
        $emitter->emit($response);

        exit(0);
    }

    private function enableDebug()
    {
        //Enable Debug Bar
        if (config("app.debug") && config('app.env') === 'development') {

            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        }
    }

    /**
     * [renderTime description] it will return application render time
     * @return [type] [description]
     */
    public function calcRenderTime()
    {
        $start = START_APP;
        $end = microtime(true);
        //$renderTime = ($end - $start);
        $this->renderTime = number_format(($end - $start), 4) . " seconds";
    }
}
