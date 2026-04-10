# API Reference

This section contains complete reference documentation for the Marwa Framework.

## What's Inside

| Category | Description |
|----------|-------------|
| [Application](application.md) | Application class and methods |
| [Configuration](config.md) | All configuration keys |
| [Middleware](middleware.md) | Available middleware |
| [Events](events.md) | Framework events |
| [Facades](facades.md) | Facade reference |
| [Helpers](helpers.md) | Helper functions |

## Quick Reference

### Core Methods

```php
// Application
$app = new Application($basePath);
$app->boot();
$app->make($class);
$app->handle($request);

// Router
Router::get($path, $handler);
Router::post($path, $handler);
Router::put($path, $handler);
Router::delete($path, $handler);
```

### Facades

```php
// Available facades
app()          // Application instance
cache()        // Cache service
config()       // Configuration
event()       // Event dispatcher
http()        // HTTP client
mail()        // Mail service
router()      // Router
security()    // Security
session()     // Session
storage()     // Storage
view()        // View renderer
```

### Helpers

```php
// Available helpers
base_path($path);
config($key, $default);
app($abstract);
route($name);
redirect($to);
back();
view($template, $data);
```

## Finding What You Need

| If you need... | Look at... |
|---------------|-----------|
| How to configure app | [Configuration](config.md) |
| Available events | [Events](events.md) |
| Middleware options | [Middleware](middleware.md) |
| Helper functions | [Helpers](helpers.md) |
| Facade methods | [Facades](facades.md) |

## Related Sections

- [Tutorials](../tutorials/index.md) - Step-by-step guides
- [Architecture](../architecture/index.md) - How it works
- [Recipes](../recipes/index.md) - Common tasks