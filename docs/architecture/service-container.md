# Service Container

The service container is the core of dependency injection in Marwa Framework. It manages class instantiation, dependency resolution, and service binding.

## Overview

Marwa uses [League\Container](https://container.thephpleague.com/) as its underlying container with automatic dependency injection via reflection.

## Accessing the Container

### Using the `app()` Helper

```php
// Get the container itself
$container = app();

// Get a bound service
$cache = app(CacheInterface::class);
$logger = app(LoggerInterface::class);
```

### Using Dependency Injection

```php
use Marwa\Framework\Contracts\CacheInterface;
use Psr\Log\LoggerInterface;

class UserRepository
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}
}

// Auto-resolved via constructor
$repository = app(UserRepository::class);
```

## Binding Services

### Binding Interfaces to Implementations

```php
use Marwa\Framework\Application;
use App\Services\PaymentProcessor;
use App\Contracts\PaymentGatewayInterface;

$app->add(PaymentGatewayInterface::class, PaymentProcessor::class);

// Now inject the interface
$gateway = app(PaymentGatewayInterface::class);
```

### Binding Singletons

```php
// Same instance returned every time
$app->addShared(MyService::class, new MyService());

// Or via closure
$app->addShared(CacheInterface::class, function () {
    return new RedisCache();
});
```

### Binding Instances

```php
$service = new MyService();
$app->addShared(MyService::class, $service);
```

## Resolving Services

### Automatic Resolution

The container automatically resolves dependencies via reflection:

```php
class OrderController
{
    public function __construct(
        private OrderRepository $orders,
        private PaymentGateway $payments,
        private LoggerInterface $logger
    ) {}
}

// All dependencies automatically resolved
$controller = app(OrderController::class);
```

### Manual Resolution

```php
// Simple resolution
$service = app(MyService::class);

// With arguments
$service = new MyService($arg1, $arg2);
$app->add(MyService::class, $service);
```

## Service Provider Style Binding

### Using League Container Methods

```php
use League\Container\Container;

$container = app()->container();

// Add with factory
$container->add(MyService::class)
    ->addArgument(Database::class)
    ->addArgument(LoggerInterface::class);

// Shared (singleton)
$container->addShared(CacheInterface::class, RedisCache::class);

// Inflector for method injection
$container->inflector(MyService::class)
    ->invokeMethod('setLogger', [LoggerInterface::class]);
```

## Quick Access Helpers

### Config

```php
// Get configuration value
$dbHost = config('database.host', 'localhost');

// Get config object
$config = app(\Marwa\Framework\Supports\Config::class);
```

### Cache

```php
// Get cache instance
$cache = cache();

// Get cached value
$value = cache('key', 'default');

// Set cache
cache()->set('key', $value, 3600);
```

### Storage

```php
// Get storage instance
$storage = storage();

// Get specific disk
$storage = storage('local');
```

### Database

```php
// Get database manager
$db = db();

// Get default connection
$conn = $db->connection();

// Get specific connection
$conn = $db->connection('pgsql');
```

## Application Instance Methods

### Setting Values

```php
// Set a simple value
$app->set('app.version', '1.0.0');

// Set an instance
$app->add(MySingleton::class, new MySingleton());
```

### Checking Existence

```php
if ($app->has(CacheInterface::class)) {
    // Cache is bound
}
```

## Lifecycle

1. **Bootstrap**: Core services bound in `CoreBindingsBootstrapper`
2. **Boot**: Service providers register additional services
3. **Request**: Services resolved as needed
4. **Shutdown**: Sessions closed, responses sent

## Best Practices

1. **Favor dependency injection** over `app()` calls in constructors
2. **Bind interfaces** to implementations for flexibility
3. **Use singletons** for services that maintain state
4. **Avoid service location** (using `app()` throughout controllers)
5. **Keep containers focused** - don't overload with unrelated services
