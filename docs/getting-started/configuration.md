# Configuration

Marwa Framework uses a simple PHP configuration system with environment variable support. Configuration files are stored in the `config/` directory.

## Environment Variables

Copy `.env.example` to `.env` and configure:

```env
APP_NAME="My Application"
APP_ENV=local
APP_KEY=base64:your-32-byte-key-here
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
LOG_CHANNEL=file
```

## Configuration Files

### Application Config

`config/app.php`:

```php
return [
    'providers' => [
        Marwa\Framework\Providers\KernelServiceProvider::class,
        App\Providers\AppServiceProvider::class,
    ],
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\SessionMiddleware::class,
        Marwa\Framework\Middlewares\MaintenanceMiddleware::class,
        Marwa\Framework\Middlewares\SecurityMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class,
    ],
    'debugbar' => env('DEBUGBAR_ENABLED', false),
    'collectors' => [],
    'maintenance' => [
        'template' => 'maintenance.twig',
        'message' => 'Service temporarily unavailable',
    ],
    'error404' => [
        'template' => 'errors/404.twig',
    ],
];
```

### Database Config

`config/database.php`:

```php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'myapp'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => database_path('database.sqlite'),
        ],
    ],
];
```

### Session Config

`config/session.php`:

```php
return [
    'enabled' => true,
    'autoStart' => false,
    'name' => 'marwa_session',
    'lifetime' => 7200,
    'path' => '/',
    'domain' => '',
    'secure' => env('APP_ENV') === 'production',
    'httpOnly' => true,
    'sameSite' => 'Lax',
    'encrypt' => true,
];
```

### Security Config

`config/security.php`:

```php
return [
    'enabled' => true,
    'csrf' => [
        'enabled' => true,
        'field' => '_token',
        'header' => 'X-CSRF-TOKEN',
        'token' => '__marwa_csrf_token',
        'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'except' => [],
    ],
    'trustedHosts' => [],
    'trustedOrigins' => [],
    'throttle' => [
        'enabled' => true,
        'prefix' => 'security',
        'limit' => 60,
        'window' => 60,
    ],
];
```

### Cache Config

`config/cache.php`:

```php
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
        ],
        'array' => [
            'driver' => 'array',
        ],
    ],
];
```

### Mail Config

`config/mail.php`:

```php
return [
    'driver' => env('MAIL_MAILER', 'smtp'),
    'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
    'port' => env('MAIL_PORT', 587),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'My App'),
    ],
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'username' => env('MAIL_USERNAME'),
    'password' => env('MAIL_PASSWORD'),
];
```

### Logger Config

`config/logger.php`:

```php
return [
    'enable' => env('APP_DEBUG', false),
    'filter' => [
        'password',
        'secret',
        'token',
    ],
    'level' => 'debug',
    'storage' => [
        'driver' => env('LOG_CHANNEL', 'file'),
        'path' => storage_path('logs'),
        'prefix' => 'marwa',
    ],
];
```

## Accessing Configuration

### Using Config Helper

```php
// Get a value
$debug = config('app.debugbar');

// Get with default
$port = config('database.connections.mysql.port', 3306);

// Set a value
config(['app.debugbar' => true]);
```

### Using Environment Function

```php
$env = env('APP_ENV', 'production');
$debug = env('APP_DEBUG', false);
```

## Configuration Caching

In production, cache configuration for performance:

```bash
php marwa config:cache
```

Clear the cache:

```bash
php marwa config:clear
```

## Best Practices

1. **Never commit secrets** - Use `.env` for sensitive values
2. **Use environment variables** - For environment-specific settings
3. **Provide defaults** - Always have fallback values
4. **Group related settings** - Keep related configs together
5. **Validate in boot** - Check required configs early
