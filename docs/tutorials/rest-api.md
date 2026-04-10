# REST API Guide

This guide covers building RESTful APIs with the Marwa Framework.

## Overview

Build modern REST APIs with:
- JSON responses
- Resource routing
- Input validation
- Authentication
- Error handling

## Quick Start

### Create API Controller

```php
// app/Controllers/Api/UserController.php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use Marwa\Framework\Http\Controller;
use Marwa\Router\Response;

final class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::all();
        
        return response()->json([
            'data' => $users->toArray(),
        ]);
    }

    public function show(int $id): Response
    {
        $user = User::findOrFail($id);
        
        return response()->json([
            'data' => $user->toArray(),
        ]);
    }

    public function store(Request $request): Response
    {
        // Validate
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        // Create
        $user = User::create($request->all());
        
        return response()->json([
            'data' => $user->toArray(),
        ], 201);
    }

    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        
        $this->validate($request, [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
        ]);

        $user->update($request->all());
        
        return response()->json([
            'data' => $user->toArray(),
        ]);
    }

    public function destroy(int $id): Response
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return response()->json(null, 204);
    }
}
```

### Define Routes

```php
// routes/api.php
use App\Controllers\Api\UserController;
use Marwa\Framework\Facades\Router;

Router::resource('users', UserController::class);
```

## Resource Routing

### Auto-Generated Routes

```php
Router::resource('users', UserController::class);
```

Creates:

| Method | Endpoint | Controller Method |
|-------|----------|-----------------|
| GET | /users | index |
| GET | /users/{id} | show |
| POST | /users | store |
| PUT/PATCH | /users/{id} | update |
| DELETE | /users/{id} | destroy |

### Custom Routes

```php
Router::get('/users/export', [UserController::class, 'export']);
Router::post('/users/{id}/activate', [UserController::class, 'activate']);
```

## Response Formats

### JSON Response

```php
return response()->json([
    'data' => $user->toArray(),
]);
```

### Collection Response

```php
return response()->json([
    'data' => $users->toArray(),
    'meta' => [
        'total' => $users->total(),
        'page' => $users->currentPage(),
        'per_page' => $users->perPage(),
    ],
]);
```

### Error Response

```php
return response()->json([
    'error' => [
        'code' => 'USER_NOT_FOUND',
        'message' => 'User not found',
    ],
], 404);
```

### Success Response

```php
return response()->json([
    'message' => 'User created successfully',
    'data' => $user->toArray(),
], 201);
```

## Authentication

### Using API Tokens

```php
// User Model
protected array $hidden = ['api_token'];

public function generateToken(): string
{
    $this->api_token = bin2hex(random_bytes(32));
    $this->save();
    return $this->api_token;
}
```

### Login Endpoint

```php
public function login(Request $request): Response
{
    $this->validate($request, [
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !password_verify($request->password, $user->password)) {
        return response()->json([
            'error' => 'Invalid credentials',
        ], 401);
    }

    return response()->json([
        'token' => $user->generateToken(),
    ]);
}
```

### Protected Routes

```php
Router::middleware('auth:api')->group(function () {
    Router::get('/profile', [UserController::class, 'profile']);
});
```

### Token Middleware

```php
// app/Http/Middleware/TokenAuth.php
use Psr\Http\Message\ServerRequestInterface;

final class TokenAuth implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');
        
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('api_token', $token)->first();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $handler->handle($request->withAttribute('user', $user));
    }
}
```

## Pagination

### Controller

```php
public function index(): Response
{
    $users = User::paginate(15);
    
    return response()->json([
        'data' => $users->items(),
        'meta' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ],
    ]);
}
```

### URL Parameters

```
GET /users?page=2&per_page=10
```

## Filtering

```php
public function index(): Response
{
    $query = User::query();

    if (request()->has('name')) {
        $query->where('name', 'like', '%' . request('name') . '%');
    }

    if (request()->has('role')) {
        $query->where('role', request('role'));
    }

    $users = $query->paginate();

    return response()->json(['data' => $users]);
}
```

### Usage

```
GET /users?role=admin
GET /users?name=John
```

## Sorting

```php
public function index(): Response
{
    $query = User::query();

    $sortBy = request('sort', 'created_at');
    $sortDir = request('dir', 'desc');

    $query->orderBy($sortBy, $sortDir);

    $users = $query->paginate();

    return response()->json(['data' => $users]);
}
```

### Usage

```
GET /users?sort=name&dir=asc
```

## Error Handling

### Validation Errors

```php
public function store(Request $request): Response
{
    try {
        $this->validate($request, [...]);
    } catch (ValidationException $e) {
        return response()->json([
            'errors' => $e->errors(),
        ], 422);
    }
}
```

### Not Found

```php
public function show(int $id): Response
{
    $user = User::find($id);
    
    if (!$user) {
        return response()->json([
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'User not found',
            ],
        ], 404);
    }
    
    return response()->json(['data' => $user]);
}
```

### Server Error

```php
// Always return proper error structure
catch (\Throwable $e) {
    logger()->error($e->getMessage());
    
    return response()->json([
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'Something went wrong',
        ],
    ], 500);
}
```

## API Versioning

### URL Versioning

```php
Router::prefix('v1')->group(function () {
    Router::resource('users', UserController::class);
});
```

```
GET /api/v1/users
```

### Header Versioning

```php
// Custom middleware
Router::get('/users', [UserController::class, 'index'])
    ->middleware('apiVersion:v1');
```

## Rate Limiting

### Basic Rate Limiting

```php
// config/http.php
return [
    'rate_limit' => [
        'default' => '60/minute',
    ],
];
```

### Custom Limits

```php
Router::middleware(' throttle:100')->group(function () {
    Router::post('/import', [ImportController::class, 'store']);
});
```

## JSON:API Compliance

### Resource Object

```php
return response()->json([
    'data' => [
        'type' => 'users',
        'id' => (string) $user->id,
        'attributes' => [
            'name' => $user->name,
            'email' => $user->email,
        ],
        'relationships' => [
            'posts' => [
                'links' => [
                    'related' => "/users/{$user->id}/posts",
                ],
            ],
        ],
    ],
    'includes' => [...],
    'meta' => [...],
]);
```

## Testing APIs

```php
public function testCanListUsers(): void
{
    $response = $this->get('/api/users');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email'],
            ],
        ]);
}

public function testCanCreateUser(): void
{
    $response = $this->post('/api/users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    $response->assertStatus(201)
        ->assertJsonFragment([
            'name' => 'John',
        ]);
}
```

## Best Practices

### 1. Consistent Response Structure

```php
// Always wrap in 'data'
return response()->json(['data' => $resource]);

// Or for collections
return response()->json(['data' => $resources, 'meta' => [...]]);
```

### 2. Proper HTTP Status Codes

| Code | Usage |
|------|-------|
| 200 | GET success |
| 201 | Created |
| 204 | No content (DELETE) |
| 400 | Bad request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not found |
| 422 | Validation error |
| 500 | Server error |

### 3. Use Standard Errors

```php
return response()->json([
    'error' => [
        'code' => 'ERROR_CODE',
        'message' => 'Human readable message',
    ],
], statusCode);
```

### 4. Document Your API

```php
/**
 * @api {GET} /users List users
 * @apiName listUsers
 * @apiGroup Users
 * @apiSuccess {Array} data List of users
 */
public function index(): Response
{
    // ...
}
```

## Related

- [Controllers](controllers.md) - Controller guide
- [Validation](validation.md) - Input validation
- [HTTP Client](http-client.md) - External APIs