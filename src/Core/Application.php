<?php declare(strict_types=1);

namespace Marwa\App\Core;

use Marwa\Logging\ErrorHandler;
use Marwa\App\Configs\Config;

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
        $this->bootstrapErrorHandler();
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
        $config = new Config(BASE_PATH,CONFIG_PATH);
        //$config->configureEnv(BASE_PATH, overload: false);
        $config->load();
        app()->singleton('config', $config);
        // You can now access configuration items via $config->get('item_name');
        // For example: $config->get('database.host');
    }

    public function run(): void
    {
        dd(app('config')->all());
        exit(0);
    }

    public function getConfigPath(): string
    {
        return $this->configPath;
    }
    public function bootstrapErrorHandler(): void
    {
       
            $handler = ErrorHandler::bootstrap([
                'app_name'       => config('app.app_name'),
                'env'            => config('app.env'),   // or 'production'
                'log_path'       => config('app.log_path'),
                'max_log_bytes'  => config('app.max_log_bytes'),
                'debug'          => config('app.debug', false),
                'log_level'      => config('app.log_level', 'error'),
                'sensitive_keys' => config('app.sensitive_keys', []),
            ]);
             // Enable Laravel-style reporter
            $handler->enableExceptionReporter();
            app()->singleton('error_handler', $handler);
            app()->singleton('logger', $handler->getLogger());
    }


}