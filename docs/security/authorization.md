# Authorization

Authorization determines what an authenticated user can do. Marwa Framework doesn't include built-in authorization, but this guide shows how to implement common patterns.

## Role-Based Access Control

### User Model with Roles

```php
// app/Models/User.php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password', 'role'];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
```

### Authorization Service

```php
// app/Services/Authorizer.php
class Authorizer
{
    public function __construct(
        private Authenticator $auth
    ) {}

    public function allows(string $ability, ?object $resource = null): bool
    {
        $user = $this->auth->user();

        if ($user === null) {
            return false;
        }

        return $this->checkPermission($user, $ability, $resource);
    }

    public function denies(string $ability, ?object $resource = null): bool
    {
        return !$this->allows($ability, $resource);
    }

    private function checkPermission(User $user, string $ability, ?object $resource): bool
    {
        return match ($ability) {
            'view-dashboard' => true,
            'manage-users' => $user->isAdmin(),
            'edit-posts' => $user->isEditor() || $user->id === $resource?->user_id,
            'delete-posts' => $user->isAdmin() || $user->id === $resource?->user_id,
            'publish-posts' => $user->isAdmin(),
            default => false,
        };
    }
}
```

### Authorization Middleware

```php
// app/Middleware/Authorize.php
class Authorize implements \Psr\Http\Server\MiddlewareInterface
{
    public function __construct(
        private Authorizer $authorizer
    ) {}

    public function process(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {
        $ability = $request->getAttribute('ability');

        if ($ability && $this->authorizer->denies($ability)) {
            return Response::forbidden('Access denied');
        }

        return $handler->handle($request);
    }
}
```

### Using in Controllers

```php
// app/Controllers/PostController.php
class PostController
{
    public function __construct(
        private Authorizer $authorizer
    ) {}

    public function edit(int $id): mixed
    {
        $post = Post::findOrFail($id);

        if ($this->authorizer->denies('edit-posts', $post)) {
            return Response::forbidden('Cannot edit this post');
        }

        return view('posts/edit', ['post' => $post]);
    }

    public function delete(int $id): mixed
    {
        $post = Post::findOrFail($id);

        $this->authorize('delete-posts', $post);

        $post->delete();

        return redirect('/posts');
    }
}
```

## Gate Pattern

```php
// app/Services/Gate.php
class Gate
{
    private array $abilities = [];

    public function define(string $ability, callable $callback): self
    {
        $this->abilities[$ability] = $callback;

        return $this;
    }

    public function allows(string $ability, array $arguments = []): bool
    {
        $callback = $this->abilities[$ability] ?? fn () => false;

        return (bool) $callback(...$arguments);
    }

    public function denies(string $ability, array $arguments = []): bool
    {
        return !$this->allows($ability, $arguments);
    }
}

// Register abilities in a service provider
$gate = app(Gate::class);

$gate->define('edit-post', function (User $user, Post $post) {
    return $user->isAdmin() || $user->id === $post->user_id;
});

$gate->define('delete-post', function (User $user, Post $post) {
    return $user->isAdmin();
});

$gate->define('manage-users', function (User $user) {
    return $user->isAdmin();
});
```

## Route Protection

### Middleware Registration

```php
// config/app.php
'middlewares' => [
    // ...
    App\Middleware\Authenticate::class,
    App\Middleware\Authorize::class,
],
```

### Route-Level Authorization

```php
// routes/web.php

// Admin only routes
Route::group(['middleware' => 'ability:manage-users'], function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
});

// Editor routes
Route::group(['middleware' => 'ability:edit-posts'], function () {
    Route::get('/posts/create', [PostController::class, 'create']);
    Route::post('/posts', [PostController::class, 'store']);
});

// Resource-based authorization
Route::get('/posts/{post}/edit', [PostController::class, 'edit'])
    ->middleware('can:edit-posts,post');
```

## Policy Classes

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return $post->published || $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}

// app/Services/PolicyRegistry.php
class PolicyRegistry
{
    private array $policies = [
        Post::class => PostPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function getPolicy(object $model): ?string
    {
        return $this->policies[get_class($model)] ?? null;
    }

    public function authorize(User $user, string $action, object $model): bool
    {
        $policy = $this->getPolicy($model);

        if ($policy === null) {
            return false;
        }

        $method = $this->toMethod($action);

        return (new $policy())->{$method}($user, $model);
    }

    private function toMethod(string $action): string
    {
        return lcfirst(str_replace('-', '', ucwords($action, '-')));
    }
}
```

## API Authorization

For API endpoints:

```php
// API Authorization Response
class ApiAuthorizer
{
    public function __construct(
        private Authorizer $authorizer
    ) {}

    public function authorizeOrFail(string $ability, ?object $resource = null): void
    {
        if ($this->authorizer->denies($ability, $resource)) {
            throw new HttpException(403, 'Forbidden');
        }
    }
}

// In API controller
public function update(Request $request, int $id): mixed
{
    $post = Post::findOrFail($id);

    app(ApiAuthorizer::class)->authorizeOrFail('update', $post);

    $post->update($request->all());

    return Response::json($post);
}
```

## Best Practices

1. **Always check authorization** - Never assume a user can perform an action
2. **Defense in depth** - Check at controller and database level
3. **Fail securely** - Deny access by default
4. **Log authorization failures** - Track potential security issues
5. **Separate concerns** - Use policies for complex authorization logic
6. **Test authorization rules** - Ensure users can only access what they should
