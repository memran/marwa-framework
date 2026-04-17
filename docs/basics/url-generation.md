# URL Generation

Marwa Framework provides path helper functions for generating application paths. URL generation for web routes uses the application's base URL combined with the route path.

## Path Helpers

### Available Path Functions

| Function | Description |
|----------|-------------|
| `base_path()` | Application root directory |
| `public_path()` | Public web root |
| `storage_path()` | Storage directory |
| `config_path()` | Configuration directory |
| `resources_path()` | Resources directory |
| `views_path()` | Views directory |
| `routes_path()` | Routes directory |
| `database_path()` | Database directory |
| `module_path()` | Modules directory |
| `cache_path()` | Cache directory |
| `logs_path()` | Logs directory |

### Basic Usage

```php
// Get paths
base_path();                    // /var/www/myapp
base_path('config');            // /var/www/myapp/config
public_path('css/style.css');   // /var/www/myapp/public/css/style.css
storage_path('logs/app.log');    // /var/www/myapp/storage/logs/app.log
```

## Generating URLs

### Manual URL Construction

Since Marwa Framework uses standard route paths, construct URLs manually:

```php
// Get the current request URL
$request = request();
$currentUrl = $request->getUri();

// Build URLs manually
$baseUrl = 'https://example.com';
$url = $baseUrl . '/users/' . $userId;
```

### Using Route Paths

Reference routes by their defined paths:

```php
// In views/templates
<a href="/users">Users</a>
<a href="/users/{{ $user->id }}/edit">Edit</a>
<form action="/posts" method="POST">

// In controllers (redirects)
return redirect('/dashboard');
return redirect('/users/' . $user->id);

// In responses
return redirect()->to('/login');
```

### Building Query Strings

```php
$baseUrl = '/products';
$query = http_build_query([
    'category' => 'electronics',
    'sort' => 'price',
    'order' => 'asc',
]);
$url = $baseUrl . '?' . $query;
// /products?category=electronics&sort=price&order=asc
```

### URL with Fragments

```php
$url = '/docs#installation';
$url = '/api/users#response-format';
```

## Current URL Helpers

### Get Current Path

```php
// Get current request path
$path = request()->getUri()->getPath();
// /users/profile

// Check if on specific path
if (request()->getUri()->getPath() === '/dashboard') {
    // Active state
}
```

### Full URL with Query Parameters

```php
$uri = request()->getUri();
$fullUrl = (string) $uri;
// https://example.com/path?query=value
```

## Base URL Configuration

### Environment Variables

```env
# In .env
APP_URL=https://example.com
ASSETS_URL=https://cdn.example.com
```

### Accessing App URL

```php
$appUrl = env('APP_URL', 'http://localhost');
```

## Asset URLs

### Serving Static Assets

```php
// In templates - hardcode asset paths
<link rel="stylesheet" href="/css/app.css">
<script src="/js/app.js"></script>
<img src="/images/logo.png">

// With version/cache busting
<img src="/images/logo.png?v=1.0.1">
```

### Asset Function (Future Enhancement)

```php
// Planned helper
asset('css/app.css');      // /css/app.css
asset('js/app.js');        // /js/app.js
```

## Named Routes (Planned)

The framework may support named routes in future versions:

```php
// Route definition (when available)
Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');

// URL generation
route('users.show', ['id' => 123]);  // /users/123
```

## Security Considerations

- Always validate and sanitize user input when building URLs
- Use `htmlspecialchars()` for URL parameters containing user data
- Prefer relative paths when linking within the same domain
- Use HTTPS for all external links

## Best Practices

1. **Use absolute paths for cross-domain links**
2. **Use relative paths for internal navigation**
3. **Always escape user data in URLs**
4. **Cache asset URLs when possible**
