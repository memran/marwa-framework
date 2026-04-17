# Boot Flow

This guide describes the detailed bootstrap flow of the Marwa Framework.

## HTTP Request Flow

```mermaid
flowchart TD
    A[HTTP Request] --> B[public/index.php]
    B --> C[Application Created]
    C --> D[Container Initialized]
    D --> E[.env Loaded]
    E --> F[Core Services Bound]
    F --> G[HttpKernel Boot]
    G --> H[AppBootstrapper]
    H --> I[Providers]
    I --> J[DatabaseBootstrapper]
    J --> K[Modules Loaded]
    K --> L[Middleware Pipeline]
    L --> M[Router Matches Route]
    M --> N[Controller/Handler Executes]
    N --> O[Response Created]
    O --> P[HttpKernel Terminates]
    P --> Q[Response Sent]
```

## Console Flow

```mermaid
flowchart TD
    A[CLI Command] --> B[marwa entrypoint]
    B --> C[Application Created]
    C --> D[ConsoleKernel Boot]
    D --> E[CommandRegistry]
    E --> F[Discover Commands]
    F --> G[Execute Command]
    G --> H[Output Result]
```

## Detailed HTTP Boot Sequence

### Phase 1: Application Creation

```php
// public/index.php
$app = new Application(__DIR__ . '/..');
```

1. **Application Constructor** - Sets base path, initializes container
2. **Environment Load** - Loads `.env` file
3. **Core Binding** - Registers Application, Container interfaces

### Phase 2: Bootstrap

```php
// Inside HttpKernel
$app->bootstrap();
```

1. **ErrorHandlerBootstrapper** - Registers a minimal handler before config loading
2. **AppBootstrapper** - Loads configuration
3. **ProviderBootstrapper** - Boots service providers
4. **ErrorHandlerBootstrapper** - Applies config-driven logger, debugbar, and renderer settings
5. **DatabaseBootstrapper** - Initializes database connection
6. **ModuleBootstrapper** - Loads modules

### Phase 3: Request Handling

```php
// Handle request
$response = $kernel->handle($request);
```

1. **Middleware Pipeline** - Processes global middleware
2. **Router** - Matches incoming route
3. **Controller** - Executes handler
4. **Response** - Returns HTTP response

## Core Components

```mermaid
classDiagram
    class Application {
        +boot()
        +make()
        +handle()
    }
    class HttpKernel {
        +handle(request)
        +terminate(response)
    }
    class Container {
        +addShared()
        +get()
    }
    class AppBootstrapper {
        +bootstrap()
    }
    class ProviderBootstrapper {
        +bootstrap(providers)
    }
    class DatabaseBootstrapper {
        +bootstrap()
    }
    class ModuleBootstrapper {
        +bootstrap()
    }

    Application --> Container
    HttpKernel --> Application
    HttpKernel --> AppBootstrapper
    AppBootstrapper --> ProviderBootstrapper
    AppBootstrapper --> DatabaseBootstrapper
    AppBootstrapper --> ModuleBootstrapper
```

## Service Registration Order

| Phase | Services | Description |
|-------|----------|------------|
| 1 | Config, Storage | Basic services |
| 2 | Logger, Events | Logging and events |
| 3 | Cache, HTTP, Mail | Request services |
| 4 | Notifications | Notification channels |
| 5 | Session, Security | Web services |
| 6 | Error Handler | Early handler registration and config-aware tuning |
| 7 | Database | Database connection |
| 8 | Providers | Custom providers |
| 9 | Modules | Module loading |

## Middleware Pipeline

```mermaid
flowchart LR
    A[Request] --> B[RequestIdMiddleware]
    B --> C[SessionMiddleware]
    C --> D[MaintenanceMiddleware]
    D --> E[SecurityMiddleware]
    E --> F[RouterMiddleware]
    F --> G[Route Handler]
    G --> H[Response]
    
    B -.->|add X-Request-ID| A
    C -.->|init session| B
    D -.->|check mode| C
    E -.->|apply security| D
    F -.->|dispatch route| E
```

## Console Command Discovery

```mermaid
flowchart TD
    A[ConsoleKernel] --> B[CommandRegistry]
    B --> C[Built-in Commands]
    B --> D[Config Commands]
    B --> E[Discovered Commands]
    B --> F[Module Commands]
    B --> G[Package Commands]
    
    C --> H[Symfony Console]
    D --> H
    E --> H
    F --> H
    G --> H
```

## Service Lazy Loading

The framework uses lazy loading for optimal performance:

```php
// Eager loading (before)
$container->addShared(Service::class, new Service());

// Lazy loading (current convention)
$container->addShared(Service::class, fn () => new Service());
```

Services that are lazy-loaded:
- CommandRegistry
- ConsoleKernel
- Scheduler
- RiskAnalyzer
- DBForge
- SeedRunner

## Next Steps

- [Architecture Overview](../architecture.md) - Design decisions
- [Configuration](../reference/config.md) - Config options
- [Middleware](../reference/middleware.md) - Custom middleware
