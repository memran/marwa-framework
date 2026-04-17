# Authorization

Marwa Framework provides a production-ready authorization system based on Policy Classes, Permission Strings, and RBAC (Role-Based Access Control).

## Architecture

### Framework Core (Owned by Marwa)

- `Gate` - Authorization service
- `PolicyRegistry` - Policy resolver
- `AuthorizationException` - 403-style exception
- `authorize()` helper function
- Controller trait integration
- Optional middleware

### Modules/Apps (Owned by Application)

- Policy classes
- Permission definitions
- Model-to-policy mapping
- RBAC data usage

---

## Quick Start

### 1. User Model Implementation

Your User model must implement `UserInterface` or have these methods:

```php
// app/Models/User.php
use Marwa\Framework\Authorization\Contracts\UserInterface;

class User extends Model implements UserInterface
{
    public function hasPermission(string $permission): bool
    {
        // Check direct permissions
        if (in_array($permission, $this->permissions ?? [], true)) {
            return true;
        }

        // Check role-based permissions
        foreach ($this->roles ?? [] as $role) {
            $rolePermissions = $this->getRolePermissions($role);
            if (in_array($permission, $rolePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    private function getRolePermissions(string $role): array
    {
        return match ($role) {
            'admin' => ['*'],
            'editor' => ['blog.post.*', 'blog.category.*'],
            'author' => ['blog.post.create', 'blog.post.update', 'blog.post.view'],
            default => [],
        };
    }
}
```

### 2. Register Policy

```php
// app/Providers/AppServiceProvider.php

use Marwa\Framework\Authorization\Gate;
use Marwa\Framework\Authorization\PolicyRegistry;

public function register(): void
{
    $gate = app(Gate::class);
    $registry = app(PolicyRegistry::class);

    // Manual registration
    $registry->register(Post::class, PostPolicy::class);
    $registry->register(User::class, UserPolicy::class);
}
```

---

## Permission Naming Format

```
{module}.{resource}.{action}
```

**Examples:**

- `blog.post.viewAny` - List all posts
- `blog.post.view` - View a specific post
- `blog.post.create` - Create new post
- `blog.post.update` - Update a post
- `blog.post.delete` - Delete a post
- `blog.post.publish` - Custom ability

---

## Policy Classes

### Basic Policy

```php
// app/Policies/PostPolicy.php
use Marwa\Framework\Authorization\Policy;
use Marwa\Framework\Authorization\Contracts\UserInterface;

class PostPolicy extends Policy
{
    public function viewAny(UserInterface $user): bool
    {
        return $user->hasPermission('blog.post.viewAny');
    }

    public function view(UserInterface $user, Post $post): bool
    {
        // Allow if user has permission OR is owner
        return $user->hasPermission('blog.post.view') 
            || $this->isOwner($user, $post);
    }

    public function create(UserInterface $user): bool
    {
        return $user->hasPermission('blog.post.create');
    }

    public function update(UserInterface $user, Post $post): bool
    {
        // Admin or owner can update
        return $user->hasPermission('blog.post.update') 
            || $this->isOwner($user, $post);
    }

    public function delete(UserInterface $user, Post $post): bool
    {
        return $user->hasPermission('blog.post.delete');
    }

    public function publish(UserInterface $user, Post $post): bool
    {
        return $user->hasPermission('blog.post.publish');
    }
}
```

### Using in Controller

```php
// app/Controllers/PostController.php
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Controllers\Concerns\AuthorizesRequests;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index(): ResponseInterface
    {
        $this->authorize('viewAny', Post::class);
        // or: $this->authorizeClass('blog.post.viewAny');
        
        $posts = Post::all();
        return $this->view('posts.index', ['posts' => $posts]);
    }

    public function show(int $id): ResponseInterface
    {
        $post = Post::findOrFail($id);
        $this->authorize('view', $post);

        return $this->view('posts.show', ['post' => $post]);
    }

    public function store(Request $request): ResponseInterface
    {
        $this->authorize('create', Post::class);

        $post = Post::create($request->all());
        return $this->redirect('/posts/' . $post->id);
    }

    public function update(Request $request, int $id): ResponseInterface
    {
        $post = Post::findOrFail($id);
        
        // Throws AuthorizationException on failure
        $this->authorize('update', $post);

        $post->update($request->all());
        return $this->json($post);
    }

    public function destroy(int $id): ResponseInterface
    {
        $post = Post::findOrFail($id);
        $this->authorize('delete', $post);

        $post->delete();
        return $this->json(['success' => true]);
    }
}
```

---

## Using Helper Functions

```php
// Direct authorization check (returns bool)
can('viewAny', Post::class);  // true/false
can('update', $post);          // true/false

// Throws exception on failure
authorize('viewAny', Post::class);
authorize('update', $post);

// Gate facade
gate()->allows('viewAny', Post::class);
gate()->denies('delete', $post);

// Using before callback
$gate = app(Gate::class);
$gate->before(function ($user, $ability, $resource) {
    if ($user->isAdmin()) {
        return true; // Skip policy check for admins
    }
    return null; // Continue to policy check
});
```

---

## Middleware

### Route-Level Authorization

```php
// routes/web.php
use Marwa\Framework\Middlewares\AuthorizeMiddleware;

// Via middleware parameter
Route::get('/posts', [PostController::class, 'index'])
    ->middleware('ability:blog.post.viewAny');

Route::get('/posts/{post}/edit', [PostController::class, 'edit'])
    ->middleware('ability:blog.post.update,post');

Route::delete('/posts/{post}', [PostController::class, 'destroy'])
    ->middleware('ability:blog.post.delete,post');
```

### Global Middleware

```php
// config/app.php
'middlewares' => [
    // ...
    Marwa\Framework\Middlewares\AuthorizeMiddleware::class,
],
```

---

## Module Manifest Integration

```json
{
  "name": "blog",
  "version": "1.0.0",
  "policies": {
    "Post": "Modules\\Blog\\Policies\\PostPolicy",
    "Category": "Modules\\Blog\\Policies\\CategoryPolicy"
  },
  "permissions": [
    "blog.post.viewAny",
    "blog.post.view",
    "blog.post.create",
    "blog.post.update",
    "blog.post.delete",
    "blog.post.publish",
    "blog.category.viewAny",
    "blog.category.create",
    "blog.category.update",
    "blog.category.delete"
  ],
  "menu": {
    "name": "blog",
    "label": "Blog",
    "url": "/admin/blog",
    "permission": "blog.post.viewAny"
  }
}
```

---

## Auto-Registration from Config

```php
// Load policies from config
$registry = app(PolicyRegistry::class);
$registry->loadFromConfig(config('auth.policies', []));
```

```php
// config/auth.php
<?php

return [
    'policies' => [
        Post::class => App\Policies\PostPolicy::class,
        User::class => App\Policies\UserPolicy::class,
    ],
    
    'permissions' => [
        'admin' => ['*'],
        'editor' => [
            'blog.post.viewAny',
            'blog.post.create',
            'blog.post.update',
            'blog.category.viewAny',
            'blog.category.create',
        ],
        'author' => [
            'blog.post.viewAny',
            'blog.post.create',
            'blog.post.update:own',
        ],
    ],
];
```

---

## RBAC Structure

```
User
├── id
├── name
├── email
├── roles []           // e.g., ['admin', 'editor']
├── permissions []     // e.g., ['blog.post.create']
└── methods:
    ├── hasPermission(string $permission): bool
    ├── hasRole(string $role): bool
    └── isAdmin(): bool

Permission Format: {module}.{resource}.{action}
Role → Permissions Mapping: Config-based or DB-driven
```

---

## Gate Resource Helper

```php
$gate = app(Gate::class);

$gate->resource('post', Post::class);
// Equivalent to defining:
// - post.viewAny
// - post.view
// - post.create
// - post.update
// - post.delete

// Custom abilities
$gate->resource('post', Post::class, [
    'viewAny', 'view', 'create', 'update', 'delete', 'publish'
]);
```

---

## Best Practices

1. **Backend authorization must never rely only on menu visibility**
2. **Use policy methods for complex authorization logic**
3. **Prefer permission strings for simple checks**
4. **Keep framework core thin - business rules in modules**
5. **Use explicit code over magic**
6. **Test authorization rules thoroughly**

---

## Exception Handling

```php
use Marwa\Framework\Exceptions\AuthorizationException;

try {
    $this->authorize('delete', $post);
} catch (AuthorizationException $e) {
    // $e->getAbility() - the action (e.g., 'delete')
    // $e->getResource() - the resource (e.g., Post instance)
    return $this->forbidden('Cannot delete this post');
}
```

---

## Facades

```php
// Gate facade
Gate::allows('viewAny', Post::class);
Gate::denies('delete', $post);

// Auth facade
Auth::user();           // Get current user
Auth::check();          // Is authenticated?
Auth::id();             // Current user ID
```