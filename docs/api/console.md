# Console API

## `Marwa\Framework\Console\ConsoleKernel`

- `application(): ConsoleApplication` boots and returns the Symfony Console application
- `handle(): int` runs the console application and returns the exit code
- `registerCommand(object|string $command): void` registers one command class or instance
- `registerCommands(iterable $commands): void` registers multiple command classes or instances

## `Marwa\Framework\Console\AbstractCommand`

Use this base class for framework-aware commands. It provides:

- `app()` for the framework application instance
- `container()` for the shared League container
- `config()` for the config repository
- `logger()` for the PSR-3 logger
- `basePath()` for filesystem paths inside the host app

## `config/console.php`

Supported keys:

- `name`: console application name
- `version`: console application version
- `commands`: list of command classes
- `discover`: list of `namespace` and `path` discovery rules
- `autoDiscover`: optional package discovery rules
- `stubsPath`: override the default AI helper stub directory (`src/Stubs/ai` by default)
