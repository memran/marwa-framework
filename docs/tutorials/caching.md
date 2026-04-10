# Caching Guide

This guide covers using the cache system to improve performance.

## Overview

Caching stores frequently accessed data in memory for fast retrieval:

- Reduce database queries
- Speed up API responses
- Store computed values

## Configuration

### config/cache.php

```php
return [
    'default' => 'file',

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache/framework'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],
];
```

## Basic Usage

### Using Cache Facade

```php
use Marwa\Framework\Facades\Cache;

// Store value
Cache::put('key', 'value', 60); // 60 seconds

// Store forever
Cache::forever('key', 'value');

// Retrieve
$value = Cache::get('key');

// Retrieve with default
$value = Cache::get('key', 'default');

// Check exists
if (Cache::has('key')) {
    // ...
}
```

## Cache Operations

### Store

```php
// Store for specified seconds
Cache::put('user_1', $user, 300);

// Store forever
Cache::forever('user_1', $user);
```

### Retrieve

```php
// Simple get
$user = Cache::get('user_1');

// With default
$user = Cache::get('user_1', function () {
    return User::find(1);
});

// Check first, then get
$user = Cache::remember('user_1', 60, function () {
    return User::find(1);
});
```

### Delete

```php
// Delete single
Cache::forget('user_1');

// Clear all
Cache::flush();
```

## Cache Tags

### Basic Tags

```php
// Store with tag
Cache::put('users', $users, 60);
Cache::tag('users')->put('user_1', $user);

// Get from tag
Cache::tag('users')->get('user_1');

// Clear tag
Cache::tag('users')->flush();
```

### Use Cases

```php
// Tag by model type
Cache::tag('users')->flush();
Cache::tag('posts')->flush();
Cache::tag('comments')->flush();
```

## Cache Times

### Time Constants

```php
use Marwa\Framework\Supports\Cache;

// Minutes
Cache::put('key', $value, 60); // 1 minute
Cache::put('key', $value, 300); // 5 minutes

// Using Cache helper
Cache::put('key', $value, now()->addDay());
Cache::put('key', $value, now()->addHours(2));
```

## Common Patterns

### Database Queries

```php
// Without cache
$users = User::all();

// With cache
$users = Cache::remember('users.all', 300, function () {
    return User::all();
});

// Update on change
public function updateUser(Request $request, int $id): Response
{
    $user = User::findOrFail($id);
    $user->update($request->all());
    
    Cache::forget('users.all');
    
    return response()->json(['data' => $user]);
}
```

### API Responses

```php
public function users(): Response
{
    $users = Cache::remember('api:users', 60, function () {
        return User::select('id', 'name', 'email')->get();
    });
    
    return response()->json(['data' => $users]);
}
```

### Computed Values

```php
$stats = Cache::remember('stats:dashboard', 300, function () {
    return [
        'total_users' => User::count(),
        'total_orders' => Order::count(),
        'revenue' => Order::sum('amount'),
    ];
});
```

## Multiple Stores

### Using Specific Store

```php
$value = Cache::store('redis')->get('key');
Cache::store('file')->put('key', 'value', 60);
```

### Switching Stores

```php
// Default is 'file', switch to 'redis'
Cache::store('redis')->put('key', $value);
```

## Increment/Decrement

### Counter Cache

```php
// Store counter
Cache::increment('visits');
Cache::increment('visits', 5);

// Retrieve and decrement
$visits = Cache::decrement('visits');
```

### Use Case

```php
public function visit(): Response
{
    $visits = Cache::increment('page_visits:' . $page->id);
    
    return response()->json(['visits' => $visits]);
}
```

## Cache Middleware

### Using Cache Middleware

```php
// Cache response for 60 seconds
Router::get('/api/users', [UserController::class, 'index'])
    ->middleware('cache:60');
```

## Cache Events

### Listen to Events

```php
// In service provider
use Marwa\Framework\Contracts\EventDispatcherInterface;

$events = $container->get(EventDispatcherInterface::class);

$events->listen(CacheHit::class, function (CacheHit $event) {
    logger()->info('Cache hit', ['key' => $event->key]);
});
```

## Performance Tips

### 1. Cache Expensive Operations

```php
// Good - cache query
$users = Cache::remember('users:page:' . $page, 300, function () {
    return User::orderBy('name')->paginate(20);
});

// Bad - cache simple values
Cache::put('simple', 'value');
```

### 2. Use Appropriate TTL

```php
// Short TTL for frequently changing data
Cache::put('trending', $trending, 60); // 1 minute

// Longer TTL for static data
Cache::put('config', $config, 3600); // 1 hour
```

### 3. Clear Cache Strategic

```php
// On model update
public function update(Request $request): Response
{
    $user->update($request->all());
    
    // Clear related cache
    Cache::forget('user_' . $user->id);
    Cache::forget('users.all');
    Cache::tag('users')->flush();
    
    return response()->json(['data' => $user]);
}
```

### 4. Use Tags for Related Data

```php
// User related cache
Cache::tag('user_' . $user->id)->put('profile', $profile);
Cache::tag('user_' . $user->id)->put('posts', $posts);
Cache::tag('user_' . $user->id)->flush();

// Clear all user data
Cache::tag('user_' . $user->id)->flush();
```

## Cache Drivers

### File Cache

```php
// config/cache.php
return [
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache/framework'),
        ],
    ],
];
```

### Redis Cache

```php
// config/cache.php
return [
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
    ],
];
```

## Troubleshooting

### Cache Not Working

1. Check storage permissions:
```bash
chmod -R 775 storage/
```

2. Check store configured:
```php
var_dump(Cache::get('test_key'));
```

### Cache Hit Rate Low

1. Increase TTL
2. Check cache keys are consistent
3. Use cache tags for related data

### Old Data Returned

1. Clear cache:
```bash
php marwa cache:clear
```

2. Check TTL is not too long
3. Ensure cache is cleared on updates

## Console Commands

| Command | Description |
|---------|-------------|
| `cache:clear` | Clear all cache |
| `cache:forget key` | Delete specific key |

## Related

- [Configuration](../reference/config.md) - Config options
- [Performance Tips](deployment.md) - Production optimization