<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use League\Container\Container;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\ConsoleCommandConfiguratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;

final class CommandRegistry
{
    /**
     * @var list<object|string>
     */
    private array $definitions = [];

    public function __construct(
        private Application $app,
        private Container $container,
        private LoggerInterface $logger
    ) {}

    public function register(object|string $command): void
    {
        $this->definitions[] = $command;
    }

    /**
     * @param iterable<object|string> $commands
     */
    public function registerMany(iterable $commands): void
    {
        foreach ($commands as $command) {
            $this->register($command);
        }
    }

    public function registerConfigurator(ConsoleCommandConfiguratorInterface|string $configurator): void
    {
        if (is_string($configurator)) {
            if (!class_exists($configurator)) {
                $this->logger->warning('Skipping missing console configurator.', [
                    'configurator' => $configurator,
                ]);
                return;
            }

            $configurator = $this->container->get($configurator);
        }

        if (!$configurator instanceof ConsoleCommandConfiguratorInterface) {
            $this->logger->warning('Skipping invalid console configurator.', [
                'configurator' => is_object($configurator) ? $configurator::class : get_debug_type($configurator),
            ]);
            return;
        }

        $configurator->registerCommands($this);
    }

    /**
     * @return list<Command>
     */
    public function resolve(): array
    {
        $resolved = [];
        $seenClasses = [];

        foreach ($this->definitions as $definition) {
            $command = $this->resolveDefinition($definition);

            if (!$command instanceof Command) {
                continue;
            }

            $class = $command::class;

            if (isset($seenClasses[$class])) {
                continue;
            }

            $seenClasses[$class] = true;
            $resolved[] = $this->prepare($command);
        }

        return $resolved;
    }

    /**
     * @return list<object|string>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    private function resolveDefinition(object|string $definition): ?Command
    {
        if ($definition instanceof Command) {
            return $definition;
        }

        if (!class_exists($definition)) {
            $this->logger->warning('Skipping missing console command.', [
                'command' => $definition,
            ]);
            return null;
        }

        $resolved = $this->container->get($definition);

        if (!$resolved instanceof Command) {
            $this->logger->warning('Skipping invalid console command registration.', [
                'command' => $definition,
                'resolved' => is_object($resolved) ? $resolved::class : get_debug_type($resolved),
            ]);
            return null;
        }

        return $resolved;
    }

    private function prepare(Command $command): Command
    {
        if ($command instanceof AbstractCommand) {
            $command->setMarwaApplication($this->app);
        }

        return $command;
    }
}
