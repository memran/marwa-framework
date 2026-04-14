# Architecture

## Overview

Marwa Framework is intentionally small. The architecture is built around a few core responsibilities:

- `Application`: boots the container, environment, and core shared services
- `HttpKernel`: coordinates HTTP boot flow and request handling
- `ConsoleKernel`: builds the Symfony Console application and registers commands
- `ProviderBootstrapper`: registers configured service providers
- `ModuleBootstrapper`: loads `marwa-module` services, boots module providers, and integrates module routes, views, and console discovery
- `MenuRegistry`: collects application and module menu items and exposes the built main navigation tree
- `MiddlewareBootstrapper`: resolves and pushes middleware into the pipeline
- `CommandRegistry`: stores commands from config, code, and package integrations
- `CommandDiscovery`: resolves command classes from configured namespaces or PSR-4-mapped directories
- Adapters: connect the framework to router, logger, events, views, and error handling

## Boot Flow

1. `Application` is created with the host app base path.
2. The container is initialized with reflection-based autowiring.
3. `.env` is loaded and core singletons are bound.
4. `HttpKernel` loads app config and delegates provider, module, and middleware setup to dedicated bootstrappers.
5. Module providers are booted, menu contributions are collected, module routes are loaded, and module view namespaces are registered when enabled.
6. The request enters `RelayPipelineAdapter`.
7. `RouterMiddleware` dispatches to `marwa-router`.
8. `HttpKernel::terminate()` emits the final response.

## Console Boot Flow

1. `Application` loads the environment and binds shared services.
2. `ConsoleKernel` loads `config/app.php` and `config/console.php`.
3. `ProviderBootstrapper` registers application service providers and `ModuleBootstrapper` binds the module registry/runtime.
4. `CommandRegistry` collects built-in commands, configured commands, discovered commands, module commands, and optional package commands such as `marwa-db`.
5. `ConsoleApplication` boots the Symfony Console runtime and runs the selected command.

## Extension Points

- Add service providers through `config/app.php` under `providers`
- Add global middleware through `config/app.php` under `middlewares`
- Register routes in `routes/web.php` or `routes/api.php`
- Add modules through `config/module.php` and module manifests under your configured module paths
- Register CLI commands through `config/console.php` or `Application::registerCommand()`
- Resolve loaded modules through `Application::modules()`, `Application::hasModule()`, and `Application::module()`
- Build shared navigation through `Marwa\Framework\Navigation\MenuRegistry` or the `menu()` helper
- Configure custom not-found handling with `HttpKernel::setNotFound()`
- Extend view, logger, and event behavior with their corresponding config files

## Design Notes

- Internal framework code now prefers constructor-injected `Config`, `Application`, and contracts over facade access where practical
- Facades and global helpers remain available as consumer-facing convenience APIs
- Config contracts are formalized in `src/Config/` so defaults and expected keys are centralized
