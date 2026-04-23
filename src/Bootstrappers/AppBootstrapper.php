<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use Marwa\Framework\Adapters\Event\ApplicationBootstrapping;
use Marwa\Framework\Adapters\Event\ProvidersBootstrapped;
use Marwa\Framework\Application;
use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Supports\Config;

final class AppBootstrapper
{
    /**
     * @var array{providers:list<class-string>,middlewares:list<class-string>,debugbar:bool,collectors:list<string>}|null
     */
    private ?array $appConfig = null;

    public function __construct(
        private Application $app,
        private Config $config,
        private ProviderBootstrapper $providerBootstrapper,
        private ErrorHandlerBootstrapper $errorHandlerBootstrapper,
        private DatabaseBootstrapper $databaseBootstrapper,
        private ModuleBootstrapper $moduleBootstrapper
    ) {}

    /**
     * @return array{providers:list<class-string>,middlewares:list<class-string>,debugbar:bool,collectors:list<string>}
     */
    public function bootstrap(): array
    {
        if ($this->appConfig !== null) {
            return $this->appConfig;
        }

        $this->errorHandlerBootstrapper->bootstrapEarly();
        $this->app->dispatch(new ApplicationBootstrapping(basePath: $this->app->basePath()));

        $cacheFile = BootstrapConfig::defaults($this->app)['configCache'];

        if (is_file($cacheFile)) {
            $cached = require $cacheFile;

            if (!is_array($cached)) {
                throw new \UnexpectedValueException(sprintf('Config cache file [%s] must return an array.', $cacheFile));
            }

            $this->config->prime($cached);
        } else {
            $this->config->loadIfExists(AppConfig::KEY . '.php');
        }

        /** @var array{providers:list<class-string>,middlewares:list<class-string>,debugbar:bool,collectors:list<string>} $appConfig */
        $appConfig = array_replace_recursive(AppConfig::defaults(), $this->config->getArray(AppConfig::KEY, []));

        $this->databaseBootstrapper->bootstrap();
        $this->providerBootstrapper->bootstrap($appConfig['providers']);
        $this->app->dispatch(new ProvidersBootstrapped(providers: $appConfig['providers']));
        $this->errorHandlerBootstrapper->bootstrap();
        $this->moduleBootstrapper->bootstrap();
        $this->app->boot();
        $this->appConfig = $appConfig;

        return $this->appConfig;
    }

    public function app(): Application
    {
        return $this->app;
    }
}
