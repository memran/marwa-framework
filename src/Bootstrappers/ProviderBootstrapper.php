<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Log\LoggerInterface;

final class ProviderBootstrapper
{
    public function __construct(
        private Container $container,
        private LoggerInterface $logger
    ) {}

    /**
     * @param list<class-string> $providers
     */
    public function bootstrap(array $providers): void
    {
        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                $this->logger->warning('Skipping missing service provider.', [
                    'provider' => $providerClass,
                ]);
                continue;
            }

            if (!is_subclass_of($providerClass, ServiceProviderInterface::class)) {
                $this->logger->warning('Skipping invalid service provider registration.', [
                    'provider' => $providerClass,
                ]);
                continue;
            }

            $this->container->addServiceProvider(new $providerClass());
        }
    }
}
