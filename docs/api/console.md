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
- `buildClassTarget()` for nested PSR-4 scaffold targets like `Admin/PostController`
- `frameworkStubPath()` and `writeStubFile()` for generator commands

## `config/console.php`

Supported keys:

- `name`: console application name
- `version`: console application version
- `commands`: list of command classes
- `discover`: list of `namespace` and `path` discovery rules
- `autoDiscover`: optional package discovery rules
- `stubsPath`: override the default AI helper stub directory (`src/Stubs/ai` by default)

## Built-in Scaffold Commands

- `key:generate` outputs a cryptographically secure random key for app configuration
- `make:command` generates Symfony Console commands in `app/Console/Commands`
- `make:controller` generates controllers in `app/Http/Controllers` and extends the framework base controller
- `make:model` generates `marwa-db` models in `app/Models`
- `make:model --migration` also runs the registered `make:migration` command from `marwa-db`
- `make:module` generates a `marwa-module` folder scaffold in the first configured module path
- `make:theme` generates a `marwa-view` theme under `resources/views/themes/<name>`
