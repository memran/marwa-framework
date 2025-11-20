<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use League\Container\Container;
use Symfony\Component\Console\Application as SymfonyConsole;

/**
 * ConsoleKernel boots a Symfony Console application, registers commands, and runs it.
 * No HTTP dependencies here.
 */
final class ConsoleKernel
{
    public function __construct(
        private Container $container,
        private ?SymfonyConsole $console = null
    ) {}

    /**
     * Build and run the console application.
     *
     * @param string $name   Console app name (optional)
     * @param string $version Version string (optional)
     * @return int Exit code
     */
    public function handle(string $name = 'Marwa Console', string $version = 'v0.1.0'): int
    {
        $app = $this->console ?? new SymfonyConsole($name, $version);

        // Commands are bound by ConsoleServiceProvider as 'console.commands'
        $commandClasses = $this->container->has('console.commands')
            ? (array) $this->container->get('console.commands')
            : [];

        foreach ($commandClasses as $class) {
            // Commands were registered in the container; resolve them (with DI)
            $app->add($this->container->get($class));
        }

        return $app->run();
    }
}
