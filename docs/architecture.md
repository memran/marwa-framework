# Architecture

## Overview

Marwa Framework is intentionally small. The architecture is built around a few core responsibilities:

- `Application`: boots the container, environment, and core shared services
- `HttpKernel`: coordinates HTTP boot flow and request handling
- `ProviderBootstrapper`: registers configured service providers
- `MiddlewareBootstrapper`: resolves and pushes middleware into the pipeline
- Adapters: connect the framework to router, logger, events, views, and error handling

## Boot Flow

1. `Application` is created with the host app base path.
2. The container is initialized with reflection-based autowiring.
3. `.env` is loaded and core singletons are bound.
4. `HttpKernel` loads app config and delegates provider and middleware setup to dedicated bootstrappers.
5. The request enters `RelayPipelineAdapter`.
6. `RouterMiddleware` dispatches to `marwa-router`.
7. `HttpKernel::terminate()` emits the final response.

## Extension Points

- Add service providers through `config/app.php` under `providers`
- Add global middleware through `config/app.php` under `middlewares`
- Register routes in `routes/web.php` or `routes/api.php`
- Configure custom not-found handling with `HttpKernel::setNotFound()`
- Extend view, logger, and event behavior with their corresponding config files

## Design Notes

- Internal framework code now prefers constructor-injected `Config`, `Application`, and contracts over facade access where practical
- Facades and global helpers remain available as consumer-facing convenience APIs
- Config contracts are formalized in `src/Config/` so defaults and expected keys are centralized
