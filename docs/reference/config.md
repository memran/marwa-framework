# Configuration Reference

Complete reference for all configuration files and keys.

## Config Files Overview

| File | Purpose | Required |
|------|---------|----------|
| `app.php` | App settings | No |
| `cache.php` | Cache config | No |
| `console.php` | Console commands | No |
| `database.php` | Database settings | No |
| `debugbar.php` | Debug bar | No |
| `http.php` | HTTP settings | No |
| `logging.php` | Logging config | No |
| `mail.php` | Email config | No |
| `module.php` | Modules | No |
| `notification.php` | Notifications | No |
| `queue.php` | Queue config | No |
| `session.php` | Sessions | No |
| `view.php` | View config | No |
| `security.php` | Security | No |
| `bootstrap.php` | Bootstrap settings | No |

---

## app.php

Application-level configuration.

```php
return [
    // Service providers
    'providers' => [
        // App\Providers\MyServiceProvider::class,
    ],

    // Middleware stack
    'middlewares' => [
        // App\Http\Middleware\MyMiddleware::class,
    ],

    // Enable debug bar
    'debugbar' => env('APP_DEBUG', false),

    // Custom debug collectors
    'collectors' => [
        // Custom collectors
    ],
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `providers` | `list<class-string>` | `[]` | Service providers |
| `middlewares` | `list<class-string>` | `[]` | Global middleware |
| `debugbar` | `bool` | `false` | Enable debug bar |
| `collectors` | `list<string>` | `[]` | Custom collectors |

---

## cache.php

Cache configuration.

```php
return [
    'default' => env('CACHE_DRIVER', 'file'),

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

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `default` | `string` | `'file'` | Default cache driver |
| `stores.*.driver` | `string` | - | Cache driver |
| `stores.*.path` | `string` | - | File cache path |
| `stores.*.connection` | `string` | - | Redis connection |

---

## console.php

Console command configuration.

```php
return [
    'commands' => [
        // App\Console\Commands\MyCommand::class,
    ],

    'paths' => [
        // app_path('Console/Commands'),
    ],
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `commands` | `list<class-string>` | `[]` | Command classes |
| `paths` | `list<string>` | `[]` | Command discovery paths |

---

## database.php

Database configuration.

```php
return [
    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => database_path('app.sqlite'),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'marwa'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'marwa'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],

    // marwa-db paths
    'migrationsPath' => database_path('migrations'),
    'seedersPath' => database_path('seeders'),
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `default` | `string` | `'sqlite'` | Default connection |
| `connections.*.driver` | `string` | - | Database driver |
| `connections.*.host` | `string` | - | DB host |
| `connections.*.port` | `int` | - | DB port |
| `connections.*.database` | `string` | - | Database name |
| `connections.*.username` | `string` | - | Username |
| `connections.*.password` | `string` | - | Password |
| `migrationsPath` | `string` | - | Migrations directory |
| `seedersPath` | `string` | - | Seeders directory |

---

## debugbar.php

Debug bar configuration.

```php
return [
    'enabled' => env('APP_DEBUG', false),

    'collectors' => [
        // Custom collectors
    ],
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | `bool` | `false` | Enable debug bar |
| `collectors` | `list<string>` | `[]` | Custom collectors |

---

## http.php

HTTP configuration.

```php
return [
    'timeout' => 30,
    'max_redirects' => 5,
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `timeout` | `int` | `30` | Request timeout |
| `max_redirects` | `int` | `5` | Max redirects |

---

## logging.php

Logging configuration.

```php
return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/app.log'),
            'days' => 14,
        ],

        'syslog' => [
            'driver' => 'syslog',
            'facility' => LOG_USER,
        ],
    ],
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `default` | `string` | `'stack'` | Default channel |
| `channels.*.driver` | `string` | - | Log driver |
| `channels.*.path` | `string` | - | Log file path |
| `channels.*.days` | `int` | - | Days to keep |

---

## mail.php

Mail configuration.

```php
return [
    'driver' => env('MAIL_DRIVER', 'smtp'),

    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
        'port' => env('MAIL_PORT', 2525),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'App'),
    ],
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `driver` | `string` | `'smtp'` | Mail driver |
| `smtp.host` | `string` | - | SMTP host |
| `smtp.port` | `int` | - | SMTP port |
| `smtp.username` | `string` | - | SMTP username |
| `smtp.password` | `string` | - | SMTP password |
| `from.address` | `string` | - | From address |
| `from.name` | `string` | - | From name |

---

## session.php

Session configuration.

```php
return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'cookie_name' => env('SESSION_COOKIE', 'marwa_session'),
    'cookie_path' => '/',
    'cookie_domain' => null,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'lax',
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `driver` | `string` | `'file'` | Session driver |
| `lifetime` | `int` | `120` | Lifetime in minutes |
| `cookie_name` | `string` | - | Cookie name |
| `cookie_secure` | `bool` | - | HTTPS only |
| `cookie_httponly` | `bool` | - | HTTP only |
| `cookie_samesite` | `string` | - | SameSite policy |

---

## view.php

View configuration.

```php
return [
    'paths' => [
        resource_path('views'),
    ],
    'cachePath' => storage_path('cache/views'),
    'debug' => env('VIEW_DEBUG', false),
    'themePath' => resource_path('views/themes'),
    'activeTheme' => 'default',
    'fallbackTheme' => 'default',
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `paths` | `list<string>` | - | View directories |
| `cachePath` | `string` | - | Cache directory |
| `debug` | `bool` | - | Debug mode |
| `themePath` | `string` | - | Theme directory |
| `activeTheme` | `string` | - | Active theme |

---

## security.php

Security configuration.

```php
return [
    'enableCsrf' => true,
    'trustedProxies' => ['*'],
    'trustedHeaders' => [
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',
    ],
    'bcryptRounds' => 10,
    'hashDriver' => 'bcrypt',
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enableCsrf` | `bool` | `true` | Enable CSRF protection |
| `trustedProxies` | `list<string>` | - | Trusted proxies |
| `trustedHeaders` | `list<string>` | - | Trusted headers |
| `bcryptRounds` | `int` | `10` | Bcrypt rounds |
| `hashDriver` | `string` | `'bcrypt'` | Hash driver |

---

## bootstrap.php

Bootstrap configuration.

```php
return [
    'configCache' => base_path('bootstrap/cache/config.php'),
    'routeCache' => base_path('bootstrap/cache/routes.php'),
    'moduleCache' => base_path('bootstrap/cache/modules.php'),
];
```

### Keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `configCache` | `string` | - | Config cache path |
| `routeCache` | `string` | - | Route cache path |
| `moduleCache` | `string` | - | Module cache path |

---

## Loading Configuration

### From Your Code

```php
// Get config value
$value = config('app.providers');

// With default
$value = config('app.debug', false);

// Set config value
config(['app.debug' => true]);
```

### Environment Variables

```bash
# .env file
APP_NAME=MyApp
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=database/app.sqlite
```

## Related

- [Configuration Tutorial](../tutorials/configuration.md) - Config guide
- [API Reference](index.md) - More docs