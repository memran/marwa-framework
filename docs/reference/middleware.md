# Middleware Reference

This document lists all available middleware in the Marwa Framework.

## Built-in Middleware

| Middleware | Description | Location |
|-----------|-------------|----------|
| RequestIdMiddleware | Adds X-Request-ID header | `src/Middlewares/RequestIdMiddleware.php` |
| SessionMiddleware | Manages sessions | `src/Middlewares/SessionMiddleware.php` |
| MaintenanceMiddleware | Site maintenance mode | `src/Middlewares/MaintenanceMiddleware.php` |
| SecurityMiddleware | Security headers | `src/Middlewares/SecurityMiddleware.php` |
| RouterMiddleware | Route dispatching | `src/Middlewares/RouterMiddleware.php` |
| DebugbarMiddleware | Debug toolbar | `src/Middlewares/DebugbarMiddleware.php` |

## Default Middleware Stack

The framework applies these middleware in order:

```php
// HttpKernel.php
return [
    RequestIdMiddleware::class,
    SessionMiddleware::class,
    MaintenanceMiddleware::class,
    SecurityMiddleware::class,
    RouterMiddleware::class,
    DebugbarMiddleware::class,
];
```

## Middleware Details

### RequestIdMiddleware

Adds a unique request ID to all requests.

```php
// Usage - adds header
X-Request-ID: abc123def456
```

**Options:** None.

### SessionMiddleware

Initializes and manages session state.

```php
// config/session.php
return [
    'driver' => 'file', // or 'cookie'
    'lifetime' => 120,
    'cookie_name' => 'marwa_session',
];
```

### MaintenanceMiddleware

Shows maintenance page when enabled.

```php
// Enable maintenance mode
// Place a file at storage/framework/down

// Custom page - create resources/views/errors/maintenance.twig
```

### SecurityMiddleware

Adds security headers and CSRF protection.

```php
// config/security.php
return [
    'enableCsrf' => true,
    'trustedProxies' => ['*'],
    'trustedHeaders' => [
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',
    ],
];
```

Headers added:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security` (if HTTPS)

### RouterMiddleware

Routes requests to handlers.

```php
// Uses marwa-router for route matching
// Dispatches to matched controller/closure
```

### DebugbarMiddleware

Injects debug toolbar in HTML responses.

```php
// config/debugbar.php
return [
    'enabled' => env('APP_DEBUG', false),
    'collectors' => [
        // Custom collectors
    ],
];
```

## Custom Middleware

### Creating Middleware

```php
// app/Http/Middleware/AuthMiddleware.php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Check authentication
        $user = $request->getAttribute('user');
        
        if ($user === null) {
            return redirect('/login');
        }
        
        // Add user to request
        $request = $request->withAttribute('user', $user);
        
        return $handler->handle($request);
    }
}
```

### Registering Middleware

In `config/app.php`:

```php
return [
    'middlewares' => [
        App\Http\Middleware\AuthMiddleware::class,
    ],
];
```

### Middleware Priority

Middleware runs in order. Add your middleware:

- At the beginning (before routing)
- At the end (after routing)

```php
// config/app.php
return [
    'middlewares' => [
        // Runs first
        RequestIdMiddleware::class,
        SessionMiddleware::class,
        YourCustomMiddleware::class,
        // Runs last
        RouterMiddleware::class,
    ],
];
```

## Middleware Parameters

Some middleware accept parameters:

```php
// With parameters
'session' => [
    'driver' => 'file',
    'lifetime' => 60,
],
```

## Related

- [Security Tutorial](../tutorials/security.md) - Security features
- [Controllers Tutorial](../tutorials/controllers.md) - HTTP handling
- [Configuration](../api/configuration.md) - Config reference