<?php

namespace Marwa\App\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Marwa\App\Configs\Config;
use Marwa\App\Configs\Env;
use Marwa\App\Requests\Request;
use Marwa\App\Core\Debug;
use Marwa\Logging\ErrorHandler;
use Marwa\App\Http\Response\ResponseFactory;
use Marwa\App\Support\Runtime;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Marwa\App\Core\Translate;
use Marwa\App\Views\{ViewFactory, TwigCompiler};

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
            'request',
            'response',
            'emitter',
            'view',
            'lang'
        ];

        return in_array($id, $services);
    }
    /**
     * Apply timezone and locale as early as possible.
     */
    private function timezone(): void
    {
        $tz = env('APP_TIMEZONE', 'UTC');
        @date_default_timezone_set($tz);

        if (\class_exists(\Locale::class)) {
            $locale = env('APP_LOCALE', 'en_US');
            @\Locale::setDefault($locale);
        }
    }

    public function register(): void
    {

        if (Runtime::isWeb()) {
            //d("Loading from register method on AppBootServer");
            $this->getContainer()->addShared('request', function () {
                return new Request();
            });
            $this->getContainer()->addShared('response', function () {
                return new ResponseFactory();
            });

            $this->getContainer()->addShared('emitter', function () {
                return new SapiEmitter();
            });

            $this->getContainer()->addShared('view', function () {
                $factory = new ViewFactory(VIEWS_PATH);
                TwigCompiler::compile($factory);
                return $factory;
            });

            $this->getContainer()->addShared('lang', function () {
                return Translate::getInstance();
            });
        }
    }

    public function boot(): void
    {
        //dd("Loading from boot method on AppBootServer");
        $this->getContainer()->addShared('env', function () {
            return new Env(BASE_PATH);
        });

        if (env('APP_ENV') === 'development') {
            Debug::enable();
        }

        if (env('APP_DEBUG', false)) {
            $this->getContainer()->addShared('error_handler', function () {

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
            $this->getContainer()->addShared('logger', function () {
                return app('error_handler')->getLogger();
            });
        }
        $this->getContainer()->addShared('config', function () {
            $config = new Config(BASE_PATH);
            $config->load();
            return $config;
        });
    }
}
