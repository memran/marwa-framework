<?php

namespace Marwa\App\Core;

use League\Container\ServiceProvider\ServiceProviderInterface as LeagueServiceProvider;
use Marwa\App\Core\Container;

/**
 * Loads service providers and middleware from a config array.
 *
 * - Providers: supports class-string, instance, or factory callable(Container): ServiceProvider
 * - Middleware (PSR-15): supports class-string, instance, or factory callable(Container): MiddlewareInterface
 * - Returns route middleware map so the router can resolve aliases later
 */
final class ConfigLoader
{
    /**
     * Load League service providers from config['app']['providers'].
     */
    public static function loadProviders(Container $container, array $providers): void
    {
        $list = $providers ?? [];
        foreach ($list as $entry) {
            //$provider = self::resolveProvider($container, $entry);
            // Register with underlying League container
            $container->raw()->addServiceProvider($entry);
        }
    }

    /**
     * Normalize a provider entry into a League provider instance.
     *
     * @param Container $container
     * @param mixed $entry class-string|LeagueServiceProvider|callable(Container):LeagueServiceProvider
     * @return LeagueServiceProvider
     */
    private static function resolveProvider(Container $container, mixed $entry): LeagueServiceProvider
    {
        if ($entry instanceof LeagueServiceProvider) {
            return $entry;
        }

        if (is_callable($entry)) {
            $prov = $entry($container);
            self::assertProvider($prov);
            return $prov;
        }

        if (is_string($entry)) {
            // Instantiate via container so constructor deps are injected
            /** @var LeagueServiceProvider $prov */
            $prov = $container->make($entry);
            self::assertProvider($prov);
            return $prov;
        }

        throw new \InvalidArgumentException('Invalid provider entry type.');
    }

    
    private static function assertProvider(mixed $prov): void
    {
        if (!$prov instanceof LeagueServiceProvider) {
            $type = is_object($prov) ? $prov::class : gettype($prov);
            throw new \UnexpectedValueException("Provider must implement League ServiceProviderInterface, got {$type}.");
        }
    }

}
