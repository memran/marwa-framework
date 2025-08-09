<?php

namespace Marwa\App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Log\LoggerInterface;
use Marwa\Logging\ErrorHandler;


final class LoggingServiceProvider extends AbstractServiceProvider
{
    /**
     * List of services this provider registers.
     * League uses this to know when the provider is relevant.
     *
     * @var array<class-string|string>
     */
    protected $provides = [
        'error_handler',
    ];

    public function register(): void
    {
        $container = $this->getContainer();

        $container->singleton('error_handler', function () {
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
            return $handler;
        });

        $container->singleton('logger',app()->get('error_handler')->getLogger());
        d("i am in logger service provider");
    }
}
