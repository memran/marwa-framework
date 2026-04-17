# Service Providers

Service providers are the central place for bootstrapping application services. They register bindings in the service container and configure the application.

## Types of Providers

### 1. League Service Provider

Standard PSR-11 compatible service provider:

```php
use Marwa\Framework\Adapters\ServiceProviderAdapter;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class MyServiceProvider extends ServiceProviderAdapter implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            MyService::class,
            MyInterface::class,
        ], true);
    }

    public function register(): void
    {
        $this->getContainer()->addShared(MyService::class, function ($container) {
            return new MyService(
                $container->get(Dependency::class)
            );
        });

        $this->getContainer()->addShared(MyInterface::class, MyService::class);
    }

    public function boot(): void
    {
        // Run after all providers are registered
    }
}
```

### 2. Module Service Provider

For module-based services:

```php
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class BlogServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->set('blog.enabled', true);
        $app->add(BlogRepository::class);
    }

    public function boot($app): void
    {
        // Routes, views, and other booted services
        $app->make(MenuRegistry::class)->add([
            'name' => 'blog',
            'label' => 'Blog',
            'url' => '/blog',
            'visible' => static fn (): bool => user()?->hasPermission('blog.post.view') === true,
        ]);
    }
}
```

Menu visibility is presentation-only. Backend access should still be enforced by controller guards, policies, or route middleware.

## Registering Providers

### Application Config

Register providers in `config/app.php`:

```php
return [
    'providers' => [
        // Framework providers
        Marwa\Framework\Providers\KernelServiceProvider::class,
        Marwa\Framework\Providers\ConsoleServiceProvider::class,

        // Application providers
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
    ],
];
```

### Manual Registration

```php
use Marwa\Framework\Application;

$app = new Application('/path/to/app');

$app->addServiceProvider(MyServiceProvider::class);
$app->addServiceProvider(new MyOtherProvider());
```

## Provider Lifecycle

### Registration Phase

Services are registered in the container:

```php
public function register(): void
{
    // Bind services to container
    $this->getContainer()->addShared(MyService::class);
}
```

### Boot Phase

Run after all services are registered:

```php
public function boot(): void
{
    // Configure services, set up routes, etc.
}
```

## Creating a Custom Provider

### 1. Create the Provider Class

```php
// app/Providers/PaymentServiceProvider.php
namespace App\Providers;

use App\Services\StripePayment;
use App\Contracts\PaymentGatewayInterface;
use Marwa\Framework\Adapters\ServiceProviderAdapter;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class PaymentServiceProvider extends ServiceProviderAdapter implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            PaymentGatewayInterface::class,
            StripePayment::class,
        ], true);
    }

    public function register(): void
    {
        $this->getContainer()->addShared(StripePayment::class, function () {
            return new StripePayment(
                config('services.stripe.secret')
            );
        });

        $this->getContainer()->addShared(
            PaymentGatewayInterface::class,
            StripePayment::class
        );
    }

    public function boot(): void
    {
        // Verify Stripe is configured
        if (!config('services.stripe.secret')) {
            throw new \RuntimeException('Stripe secret key not configured');
        }
    }
}
```

### 2. Register in Config

```php
// config/app.php
return [
    'providers' => [
        // ...
        App\Providers\PaymentServiceProvider::class,
    ],
];
```

## Deferring Providers

Defer providers for faster boot times:

```php
final class OptimizedProvider extends ServiceProviderAdapter
{
    public function provides(string $id): bool
    {
        return $id === OptimizedService::class;
    }

    public function register(): void
    {
        $this->getContainer()->addShared(OptimizedService::class);
    }
}
```

## Best Practices

1. **Single responsibility** - One provider per feature/module
2. **Register early** - Use `register()` for container bindings
3. **Boot late** - Use `boot()` for cross-service configuration
4. **Declare dependencies** - Use `provides()` for deferred providers
5. **Avoid heavy work** - Keep boot methods fast
6. **Type hints** - Enable auto-resolution benefits

## Common Framework Providers

| Provider | Purpose |
|----------|---------|
| `KernelServiceProvider` | HTTP routing, views, debugbar |
| `ConsoleServiceProvider` | Console commands, scheduling |
| Module providers | Module-specific services |

## Accessing Services in Providers

```php
public function register(): void
{
    $container = $this->getContainer();

    // Get existing service
    $config = $container->get(Config::class);

    // Create new service with dependencies
    $service = new MyService(
        $container->get(Dependency1::class),
        $container->get(Dependency2::class)
    );

    $container->addShared(MyService::class, $service);
}
```
