# Caching Guide

This guide covers using the cache system to improve performance.

## Overview

Caching stores frequently accessed data in memory for fast retrieval:

- Reduce database queries
- Speed up API responses
- Store computed values

## Configuration

### config/cache.php

The application uses a single cache store. Choose the backend with `driver`:

```php
return [
    'enabled' => true,
    'driver' => 'file', // file, memory, sqlite, apcu, nats, redis, valkey, memcached
    'namespace' => 'default',
    'file' => [
        'path' => storage_path('cache/framework'),
    ],
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 0,
        'password' => null,
        'timeout' => 1.0,
    ],
    'memcached' => [
        'servers' => [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 1],
        ],
        'persistent_id' => null,
    ],
];
```

See [Cache Drivers](#cache-drivers) below for backend-specific setup.

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

// With a default value
$user = Cache::get('user_1', null);

// Compute and store when missing
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

## Cache Times

```php
use DateInterval;
use Marwa\Framework\Facades\Cache;

Cache::put('key', $value, 60);                     // 60 seconds
Cache::put('key', $value, 3600);                   // 1 hour
Cache::put('key', $value, new DateInterval('P1D')); // 1 day
Cache::forever('key', $value);                     // no expiry
```

TTL accepts seconds as an integer, a `DateInterval`, or `null` for no expiry.

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

## Cache Collections

Only one store is active per application; switch stores by changing `driver`
in `config/cache.php`. For logical isolation inside that store, use
collections on the cache adapter:

```php
use Marwa\Framework\Adapters\Cache\ScrapbookCacheAdapter;

/** @var ScrapbookCacheAdapter $cache */
$cache = app(ScrapbookCacheAdapter::class);

$users = $cache->collection('users');
$posts = $cache->collection('posts');

$users->put('listing', ['alice'], 60);
$posts->put('listing', ['post-1'], 60);
```

Each collection gets its own namespace, so `flush()` on one does not clear the
others. Alternatively, set `namespace` in the cache config to isolate whole
applications sharing a Redis or Memcached server.

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

### 3. Invalidate on Write

```php
// On model update
public function update(Request $request): Response
{
    $user->update($request->all());

    // Clear related cache
    Cache::forget('user_' . $user->id);
    Cache::forget('users.all');

    return response()->json(['data' => $user]);
}
```

### 4. Use Collections for Related Data

```php
use Marwa\Framework\Adapters\Cache\ScrapbookCacheAdapter;

/** @var ScrapbookCacheAdapter $cache */
$cache = app(ScrapbookCacheAdapter::class);

$userCache = $cache->collection('user_' . $user->id);
$userCache->put('profile', $profile, 300);
$userCache->put('posts', $posts, 300);

// Clear only this user's entries
$userCache->flush();
```

## Cache Drivers

Set `driver` in `config/cache.php` to one of: `file`, `memory`, `array`,
`sqlite`, `apcu`, `nats`, `redis`, `valkey`, or `memcached`. An unknown driver
throws an `InvalidArgumentException` instead of silently falling back to the
file store.

### File Cache

The default driver; stores entries as signed files under `storage/cache`.

```php
return [
    'driver' => 'file',
    'file' => [
        'path' => storage_path('cache/framework'),
    ],
];
```

### Redis / Valkey

Requires the `ext-redis` (phpredis) extension. Valkey speaks the same protocol,
so `valkey` is an alias of the `redis` driver and shares the `redis` config
block.

```php
return [
    'driver' => 'redis', // or 'valkey'
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 0,
        'password' => env('REDIS_PASSWORD'),
        'timeout' => 1.0,
    ],
];
```

Connection and `AUTH`/`SELECT` failures throw a `RuntimeException` with the
host and port in the message.

### Memcached

Requires the `ext-memcached` extension. Add every server in the pool:

```php
return [
    'driver' => 'memcached',
    'memcached' => [
        'servers' => [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 1],
            ['host' => 'cache-2.internal', 'port' => 11211, 'weight' => 2],
        ],
        'persistent_id' => null,
    ],
];
```

Set `persistent_id` to reuse connections across requests.

### In-Memory, SQLite, APCu, and NATS

```php
return [
    'driver' => 'memory', // process-local; cleared at the end of the request
];
```

```php
return [
    'driver' => 'sqlite',
    'sqlite' => [
        'path' => storage_path('cache/framework.sqlite'),
        'table' => 'framework_cache',
    ],
];
```

- `apcu` uses the APCu extension and falls back to a memory store when the
  extension is unavailable.
- `nats` requires `basis-company/nats` and stores values in a JetStream
  key-value bucket; see the `nats` config block in
  [Configuration Reference](../reference/config.md#cachephp).

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
3. Use cache collections for related data

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

## Related

- [Configuration](../reference/config.md) - Config options
- [Performance Tips](deployment.md) - Production optimization