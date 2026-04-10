# Design Principles

This document explains the design philosophy and decisions behind the Marwa Framework.

## Core Principles

### 1. Simplicity Over Complexity

The framework is intentionally small. Every feature must justify its existence.

> "Simplicity is the ultimate sophistication." - Leonardo da Vinci

**Implementation:**
- Minimal core components
- Focus on composition over inheritance
- Allow extension without modification

### 2. Convention Over Configuration

Sensible defaults with customization when needed.

**Implementation:**
- Default directory structure
- Auto-discovery of commands, migrations, modules
- Configurable but not required

### 3. Performance by Default

The framework should not add unnecessary overhead.

**Implementation:**
- Lazy loading of services
- Minimal dependencies
- Efficient middleware pipeline

### 4. Developer Experience

Clear, consistent APIs that are pleasant to use.

**Implementation:**
- Type safety
- Helpful error messages
- Good documentation

## Design Decisions

### Why PSR Standards?

The framework follows PSR standards where applicable:

| PSR | Purpose | Usage |
|-----|--------|-------|
| PSR-3 | Logging | Logger interface |
| PSR-4 | Autoloading | Class loading |
| PSR-7 | HTTP | HTTP messages |
| PSR-11 | Container | Service container |
| PSR-14 | Events | Event dispatcher |
| PSR-15 | HTTP Middleware | Middleware |
| PSR-17 | HTTP Factories | Request/response |
| PSR-18 | HTTP Client | HTTP client |

### Why Not Symfony Components?

We're intentional about dependencies:

- **Full-stack frameworks** add bloat
- **Micro-frameworks** leave too much to build
- **Marwa** sits in between: lean but complete

### Why marwa-db?

`marwa-db` provides:
- Database abstraction
- Query builder
- ORM
- Migrations
- Seeders

It's built by the same team for consistency.

## Architecture Patterns

### Service Container

```php
// Register service
$container->addShared(Service::class, fn () => new Service());

// Get service (lazy)
$service = $container->get(Service::class);
```

### Dependency Injection

```php
// Constructor injection
class MyController {
    public function __construct(
        private UserService $users
    ) {}
}
```

### Facade Pattern

```php
// Static-like access
Router::get('/path', $handler);

// Behind the scenes
app(RouterInterface::class)->get(...);
```

### Event-Driven

```php
// Dispatch event
$app->dispatch(new UserRegistered($user));

// Listen for event
$app->listen(UserRegistered::class, fn ($e) => ...);
```

## Extensibility

### Service Providers

```php
// config/app.php
return [
    'providers' => [
        MyServiceProvider::class,
    ],
];
```

### Middleware

```php
// config/app.php
return [
    'middlewares' => [
        CorsMiddleware::class,
    ],
];
```

### Modules

```php
// modules/my-module/manifest.php
return [
    'providers' => [...],
    'routes' => [...],
];
```

## Performance Optimizations

### Lazy Loading

```php
// Not loaded until requested
$container->addShared(HeavyService::class, fn () => new HeavyService());
```

### Service Caching

```php
// Cached after first request
$container->addShared(Config::class, $cachedConfig);
```

### Route Caching

```bash
php marwa route:cache
```

## Code Style

The codebase follows:

- PSR-4 autoloading
- PSR-12 coding standard
- Strict types
- PHP 8.4+ features

## Contributing

Everyone is welcome to contribute:

1. Fork the repository
2. Create a feature branch
3. Add tests
4. Follow code style
5. Submit a PR

See [Contributing Guide](../developer/contributing.md) for details.

## Future Vision

The framework aims to be:

- **Lightweight** - Minimal bloat
- **Fast** - High performance
- **Extensible** - Easy to customize
- **Well-documented** - Clear guides
- **Community-driven** - Built by users

See [VISION](../VISION.md) for the long-term roadmap.

## Reference

- [Architecture](index.md) - How it all works
- [Boot Flow](boot-flow.md) - Bootstrap sequence
- [Configuration](../reference/config.md) - Config options