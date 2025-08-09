<?php

namespace Marwa\App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Marwa\Logging\ErrorHandler;

class LoggingServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * 
     */
    public function provides(string $id): bool
    {
        $services = [
            'logger',
            'error_handler'

        ];

        return in_array($id, $services);
    }

    public function register(): void
    {

        $logger = app()->get('error_handler')->getLogger();
        $this->getContainer()->add('logger', $logger);
    }

    public function boot(): void
    {

        if (config("app.debug") == true && config('app.env') === 'development') {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        }
        // post-registration actions here
        $this->getContainer()->add('error_handler', function () {
            $handler = ErrorHandler::bootstrap([
                'app_name'       => config('app.app_name'),
                'env'            => config('app.env'),   // development or 'production'
                'log_path'       => private_storage(),
                'max_log_bytes'  => config('app.max_log_bytes'),
                'debug'          => config('app.debug', false),
                'log_level'      => config('app.log_level', 'error'),
                'sensitive_keys' => config('app.sensitive_keys', []),
            ]);
            // Enable Laravel-style reporter
            $handler->enableExceptionReporter();
            return $handler;
        });
    }
}
