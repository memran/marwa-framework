# Routing

Routing connects URLs to application logic. In Marwa Framework, routes are defined in `routes/web.php` and `routes/api.php`.

## Basic Routes

### Route Files

Routes are defined in:

```
routes/
├── web.php      # Browser requests
├── api.php      # REST API endpoints
└── console.php  # CLI closures
```

### Defining Routes

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;
use App\Controllers\HomeController;

// Basic routes
Router::get('/', function() {
    return Response::html('<h1>Welcome</h1>');
})->register();

Router::get('/about', function() {
    return Response::html('<h1>About Us</h1>');
})->register();

Router::post('/contact', function() {
    // Handle POST request
    return Response::html('<h1>Thank you!</h1>');
})->register();
```

## Route Methods

Marwa Framework supports all HTTP methods:

```php
// GET requests
Router::get('/users', fn() => Response::json(['users' => []]))->register();

// POST requests
Router::post('/users', fn() => Response::json(['created' => true]))->register();

// PUT requests
Router::put('/users/{id}', fn($id) => Response::json(['id' => $id]))->register();

// PATCH requests
Router::patch('/users/{id}', fn($id) => Response::json(['updated' => true]))->register();

// DELETE requests
Router::delete('/users/{id}', fn($id) => Response::json(['deleted' => true]))->register();
```

## Route Parameters

### Required Parameters

```php
// URL: /users/123
Router::get('/users/{id}', function($id) {
    return Response::json(['user_id' => $id]);
})->register();
```

### Multiple Parameters

```php
// URL: /posts/123/comments/456
Router::get('/posts/{postId}/comments/{commentId}', function($postId, $commentId) {
    return Response::json([
        'post_id' => $postId,
        'comment_id' => $commentId
    ]);
})->register();
```

### Optional Parameters

```php
Router::get('/users/{id?}', function($id = null) {
    if ($id) {
        return Response::json(['user_id' => $id]);
    }
    return Response::json(['users' => []]);
})->register();
```

## Route Names

Name your routes for easy URL generation:

```php
Router::get('/users/profile', function() {
    return Response::html('<h1>Profile</h1>');
})->name('user.profile')->register();

Router::get('/dashboard', function() {
    return Response::html('<h1>Dashboard</h1>');
})->name('dashboard')->register();
```

## Route Groups

Group related routes with shared attributes:

```php
// Group with prefix
Router::group(['prefix' => '/admin'], function() {
    Router::get('/users', fn() => Response::html('Admin Users'))->register();
    Router::get('/settings', fn() => Response::html('Admin Settings'))->register();
});

// Group with middleware
Router::group(['middleware' => ['auth']], function() {
    Router::get('/profile', fn() => Response::html('Profile'))->register();
    Router::post('/profile', fn() => Response::html('Profile Updated'))->register();
});

// Combined groups
Router::group([
    'prefix' => '/api/v1',
    'middleware' => ['api']
], function() {
    Router::get('/users', fn() => Response::json(['users' => []]))->register();
});
```

## Controller Routes

Use controllers for cleaner code:

```php
<?php

use App\Controllers\PostController;
use App\Controllers\UserController;

// Single action
Router::get('/posts', [PostController::class, 'index'])->register();
Router::get('/posts/{id}', [PostController::class, 'show'])->register();
Router::post('/posts', [PostController::class, 'store'])->register();
Router::put('/posts/{id}', [PostController::class, 'update'])->register();
Router::delete('/posts/{id}', [PostController::class, 'destroy'])->register();
```

## Route Constraints

### Where Methods

```php
Router::get('/users/{id}', function($id) {
    return Response::json(['user_id' => $id]);
})->where('id', '[0-9]+')->register();

Router::get('/posts/{slug}', function($slug) {
    return Response::json(['slug' => $slug]);
})->where('slug', '[a-z-]+')->register();
```

### Multiple Constraints

```php
Router::get('/users/{id}/posts/{post}', function($id, $post) {
    return Response::json(['user' => $id, 'post' => $post]);
})->where([
    'id' => '[0-9]+',
    'post' => '[a-z-]+'
])->register();
```

## Route Fallback

Catch-all route for 404:

```php
Router::fallback(function() {
    return Response::html('<h1>404 - Page Not Found</h1>', 404);
});
```

## HTTPS Routes

Force HTTPS for specific routes:

```php
Router::group(['scheme' => 'https'], function() {
    Router::post('/checkout', fn() => Response::html('Checkout'))->register();
});
```

## Listing Routes

View all routes from CLI:

```bash
php marwa route:list
```

## Common Patterns

### API Routes

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;
use App\Controllers\Api\UserController;
use App\Controllers\Api\PostController;

Router::group(['prefix' => '/api'], function() {
    // Users
    Router::get('/users', [UserController::class, 'index'])->register();
    Router::get('/users/{id}', [UserController::class, 'show'])->register();
    Router::post('/users', [UserController::class, 'store'])->register();
    Router::put('/users/{id}', [UserController::class, 'update'])->register();
    Router::delete('/users/{id}', [UserController::class, 'destroy'])->register();

    // Posts
    Router::get('/posts', [PostController::class, 'index'])->register();
    Router::get('/posts/{id}', [PostController::class, 'show'])->register();
    Router::post('/posts', [PostController::class, 'store'])->register();
});
```

### Health Check

```php
Router::get('/health', function() {
    return Response::json([
        'status' => 'ok',
        'timestamp' => time(),
        'environment' => app()->environment()
    ]);
})->register();
```

## Next Steps

- [Controllers](controllers.md) - Handle route requests
- [Middleware](middleware.md) - Filter requests
- [URL Generation](url-generation.md) - Generate URLs from routes
