# Middleware

Middleware provides a convenient way to filter HTTP requests entering your application. Think of it as a chain of responsibility where each middleware can inspect, modify, or reject a request.

## How Middleware Works

```
Request → [Middleware A] → [Middleware B] → [Controller] → Response
              ↓                ↓
          Can reject      Can modify
```

## Creating Middleware

### Basic Middleware

Create `app/Http/Middleware/LogRequestsMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Marwa\Framework\Middlewares\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class LogRequestsMiddleware extends AbstractMiddleware
{
    public function handle(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        // Log the request
        logger()->info('Request received', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);

        // Continue to next middleware
        return $this->next($request);
    }
}
```

### Response Middleware

Create `app/Http/Middleware/AddHeaderMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Marwa\Framework\Middlewares\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AddHeaderMiddleware extends AbstractMiddleware
{
    public function handle(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        // Process request first
        $request = $this->next($request);

        // If we got a response back, add header
        if ($request instanceof ResponseInterface) {
            return $request->withHeader('X-Custom-Header', 'value');
        }

        return $request;
    }
}
```

## Registering Middleware

### Global Middleware

Add to `config/app.php`:

```php
return [
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        App\Http\Middleware\LogRequestsMiddleware::class,
        App\Http\Middleware\AddHeaderMiddleware::class,
    ],
];
```

### Route-Specific Middleware

```php
<?php

use Marwa\Framework\Facades\Router;
use App\Http\Middleware\AuthMiddleware;

Router::get('/dashboard', fn() => Response::html('<h1>Dashboard</h1>'))
    ->middleware(AuthMiddleware::class)
    ->register();
```

### Multiple Middleware

```php
Router::post('/admin/users', function() {
    return Response::html('<h1>Create User</h1>');
})
    ->middleware(['auth', 'admin'])
    ->register();
```

## Built-in Middleware

Marwa Framework includes several built-in middleware:

| Middleware | Description |
|-----------|-------------|
| `RequestIdMiddleware` | Adds unique request ID |
| `SessionMiddleware` | Starts/manages sessions |
| `SecurityMiddleware` | Security headers & CSRF |
| `RouterMiddleware` | Routes the request |
| `DebugbarMiddleware` | Enables debug bar |
| `MaintenanceMiddleware` | Shows maintenance page |

## Middleware Parameters

### Parameter Middleware

```php
<?php

use Marwa\Framework\Middlewares\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RoleMiddleware extends AbstractMiddleware
{
    public function __construct(
        private string $role
    ) {}

    public function handle(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        $user = $request->getAttribute('user');

        if ($user?->role !== $this->role) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        return $this->next($request);
    }
}
```

Register with parameter:

```php
Router::get('/admin', fn() => Response::html('Admin'))
    ->middleware(['role:admin'])
    ->register();
```

## Common Middleware Patterns

### Authentication Check

```php
<?php

use Marwa\Framework\Middlewares\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Marwa\Router\Response;

class AuthMiddleware extends AbstractMiddleware
{
    public function handle(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        $session = session();

        if (!$session->has('user_id')) {
            return Response::redirect('/login');
        }

        // Add user to request attributes
        $request = $request->withAttribute('user_id', $session->get('user_id'));

        return $this->next($request);
    }
}
```

### CORS Middleware

```php
<?php

use Marwa\Framework\Middlewares\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CorsMiddleware extends AbstractMiddleware
{
    public function handle(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        $response = $this->next($request);

        if ($response instanceof ResponseInterface) {
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        return $response;
    }
}
```

### Rate Limiting

```php
<?php

use Marwa\Framework\Middlewares\AbstractMiddleware;
use Marwa\Router\Response;

class ThrottleMiddleware extends AbstractMiddleware
{
    public function handle(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit:' . $ip;

        if (throttle($key, 60, 60)) {
            return Response::json([
                'error' => 'Too Many Requests'
            ], 429);
        }

        return $this->next($request);
    }
}
```

## Middleware Groups

Group middleware for reuse:

```php
// Define groups in config/app.php
return [
    'middlewareGroups' => [
        'web' => [
            App\Http\Middleware\SessionMiddleware::class,
            App\Http\Middleware\CsrfMiddleware::class,
        ],
        'api' => [
            App\Http\Middleware\ApiAuthMiddleware::class,
            App\Http\Middleware\ThrottleMiddleware::class,
        ],
    ],
];
```

## Execution Order

Middleware executes in the order they're registered:

```php
// Middleware A runs first, then B, then C
$app->addMiddleware(A::class);
$app->addMiddleware(B::class);
$app->addMiddleware(C::class);

// Request flow: A → B → C → Controller
// Response flow: Controller → C → B → A
```

## Next Steps

- [CSRF Protection](csrf-protection.md) - Protect against CSRF attacks
- [Controllers](controllers.md) - Handle requests after middleware
- [Security](security.md) - Security best practices
