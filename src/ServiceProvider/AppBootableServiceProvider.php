<?php

namespace Marwa\App\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Marwa\App\Configs\Config;
use Marwa\App\Configs\Env;
use Marwa\App\Requests\Request;
use Marwa\App\Core\Debug;
use Marwa\Logging\ErrorHandler;
use Marwa\App\Facades\Logger;
use Marwa\App\Http\Response\ResponseFactory;
use Marwa\App\Routes\Router;
use Marwa\App\Support\Runtime;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

class AppBootableServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * 
     */
    public function provides(string $id): bool
    {
        $services = [
            'env',
            'config',
            'debug',
            'error_handler',
            'logger',
            'request'
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {

        d("Loading from register method");
    }

    public function boot(): void
    {

        $this->getContainer()->add('env', function () {
            return new Env(BASE_PATH);
        });

        if (env('APP_ENV') === 'development') {
            Debug::enable();
        }

        $this->getContainer()->add('config', function () {
            $config = new Config(BASE_PATH);
            $config->load();
            return $config;
        });

        $this->getContainer()->add('error_handler', function () {

            $handler = ErrorHandler::bootstrap([
                'app_name'       => config('app.app_name'),
                'env'            => config('app.env'),   // development or 'production'
                'log_path'       => private_storage() . DS . 'logs',
                'max_log_bytes'  => config('app.max_log_bytes'),
                'debug'          => config('app.debug', false),
                'log_level'      => config('app.log_level', 'error'),
                'sensitive_keys' => config('app.sensitive_keys', []),
            ]);
            // Enable Laravel-style reporter
            $handler->enableExceptionReporter();
            return $handler;
        });
        //loading Logger
        $this->getContainer()->add('logger', function () {
            return app('error_handler')->getLogger();
        });

        if (Runtime::isWeb()) {
            $this->getContainer()->add('request', function () {
                return new Request();
            });
            $this->getContainer()->add('response', function () {
                return new ResponseFactory();
            });

            $this->getContainer()->add('router', function () {
                return new Router();
            });
            $this->getContainer()->add('emitter', function () {
                return new SapiEmitter();
            });
        }
    }
}
