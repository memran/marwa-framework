# Console Development

## Extending Commands

Extend `Marwa\Framework\Console\AbstractCommand` when you want command classes to access the application base path, config repository, logger, and service container through helper methods.

```php
final class CleanupCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger()->info('Cleanup started');

        return Command::SUCCESS;
    }
}
```

## Registration Options

- Register a class directly with `Application::registerCommand()`
- List command classes in `config/console.php`
- Use `discover` rules for PSR-4 command directories
- Expose package commands through a `ConsoleCommandConfiguratorInterface` implementation

## Package Integration

`ConsoleKernel` attempts to register `marwa-db` console commands automatically when that package exposes a compatible console configurator or command namespace. The integration is optional and safely ignored when the package is not installed.
