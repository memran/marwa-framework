<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Bootstrappers\ModuleBootstrapper;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class ConsoleKernel
{
    private bool $booted = false;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger,
        private AppBootstrapper $appBootstrapper,
        private CommandRegistry $registry,
        private CommandDiscovery $discovery,
        private ConsoleApplication $console
    ) {}

    public function handle(): int
    {
        return $this->application()->run();
    }

    public function application(): ConsoleApplication
    {
        $this->boot();

        return $this->console;
    }

    public function registerCommand(object|string $command): void
    {
        $this->registry->register($command);
    }

    /**
     * @param iterable<object|string> $commands
     */
    public function registerCommands(iterable $commands): void
    {
        $this->registry->registerMany($commands);
    }

    private function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->config->loadIfExists(ConsoleConfig::KEY . '.php');

        $this->appBootstrapper->bootstrap();
        $consoleConfig = ConsoleConfig::merge($this->app, $this->config->getArray(ConsoleConfig::KEY, []));

        $this->registry->registerMany($consoleConfig['commands']);
        $this->registry->registerMany($this->discovery->discover($consoleConfig['discover']));
        $this->registry->registerMany($this->discovery->discover($consoleConfig['autoDiscover']));
        $this->registry->registerMany($this->discoverModuleCommands());

        $this->registerMarwaDbConfigurator();

        $this->console->bootstrap(
            $consoleConfig['name'],
            $consoleConfig['version'],
            $this->registry->resolve()
        );

        $this->booted = true;
    }

    /**
     * @return list<class-string>
     */
    private function discoverModuleCommands(): array
    {
        /** @var ModuleBootstrapper $moduleBootstrapper */
        $moduleBootstrapper = $this->app->make(ModuleBootstrapper::class);

        return $this->discovery->discover($moduleBootstrapper->consoleDiscoverySources());
    }

    private function registerMarwaDbConfigurator(): void
    {
        foreach ([
            'Marwa\\Db\\Console\\ConsoleCommandConfigurator',
            'Marwa\\Db\\Console\\CommandConfigurator',
        ] as $configurator) {
            if (!class_exists($configurator)) {
                continue;
            }

            $this->registry->registerConfigurator($configurator);

            return;
        }

        $this->logger->debug('No marwa-db console configurator was discovered.');
    }
}
