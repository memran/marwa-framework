# Configuration Contracts

The framework supports optional consumer config files in `config/`. Missing files are ignored and replaced by code-level defaults defined in `src/Config/`.

## `config/app.php`

Defined by `Marwa\Framework\Config\AppConfig`.

Supported keys:

- `providers`: list of service provider class names
- `middlewares`: list of middleware class names
- `debugbar`: boolean flag for debug bar registration
- `useDebugPanel`: enable the marwa-db debug panel on the shared connection manager
- `collectors`: list of debug bar collector class names

Example:

```php
return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
    'middlewares' => [
        App\Http\Middleware\TrustProxies::class,
    ],
    'debugbar' => false,
    'useDebugPanel' => false,
    'collectors' => [],
];
```

## `config/view.php`

Defined by `Marwa\Framework\Config\ViewConfig`.

Supported keys:

- `viewsPath`: absolute or base-path-resolved views directory
- `cachePath`: writable compiled-template cache directory
- `debug`: enable template debug behavior
- `themePath`: theme root directory
- `activeTheme`: currently selected theme name
- `fallbackTheme`: fallback theme name

Example:

```php
return [
    'viewsPath' => resources_path('views'),
    'cachePath' => storage_path('cache/views'),
    'debug' => false,
    'themePath' => resources_path('views/themes'),
    'activeTheme' => 'default',
    'fallbackTheme' => 'default',
];
```

## `config/event.php`

Defined by `Marwa\Framework\Config\EventConfig`.

Supported keys:

- `listeners`: event-to-listener map
- `subscribers`: list of subscriber classes

Example:

```php
return [
    'listeners' => [
        Marwa\Framework\Adapters\Event\AppBooted::class => [
            [App\Listeners\LogApplicationBoot::class, 'handle'],
        ],
        Marwa\Framework\Adapters\Event\RequestHandled::class => [
            [App\Listeners\LogRequestHandled::class, 'handle'],
        ],
    ],
    'subscribers' => [
        App\Listeners\RuntimeSubscriber::class,
    ],
];
```

Lifecycle events available out of the box:

- `ApplicationStarted`
- `ApplicationBootstrapping`
- `ProvidersBootstrapped`
- `ErrorHandlerBootstrapped`
- `ModulesBootstrapped`
- `AppBooted`
- `RequestHandlingStarted`
- `RequestHandled`
- `AppTerminated`
- `ConsoleBootstrapped`

## `config/logger.php`

Defined by `Marwa\Framework\Config\LoggerConfig`.

Supported keys:

- `enable`: boolean logging toggle
- `filter`: list of sensitive keys to redact
- `storage.driver`: sink driver such as `file`
- `storage.path`: log output directory
- `storage.prefix`: file name prefix

## `config/mail.php`

Defined by `Marwa\Framework\Config\MailConfig`.

Supported keys:

- `enabled`: enable the mail service
- `driver`: `smtp`, `sendmail`, or `mail`
- `charset`: message charset
- `from.address`: default sender email address
- `from.name`: default sender display name
- `smtp.host`: SMTP host
- `smtp.port`: SMTP port
- `smtp.encryption`: optional SMTP encryption mode such as `tls`
- `smtp.username`: SMTP username
- `smtp.password`: SMTP password
- `smtp.authMode`: optional authentication mode
- `smtp.timeout`: SMTP timeout in seconds
- `sendmail.path`: sendmail executable path

Example:

```php
return [
    'enabled' => true,
    'driver' => 'smtp',
    'charset' => 'UTF-8',
    'from' => [
        'address' => 'no-reply@example.com',
        'name' => 'MarwaPHP',
    ],
    'smtp' => [
        'host' => '127.0.0.1',
        'port' => 1025,
        'encryption' => null,
        'username' => null,
        'password' => null,
        'authMode' => null,
        'timeout' => 30,
    ],
    'sendmail' => [
        'path' => '/usr/sbin/sendmail -bs',
    ],
];
```

## `config/http.php`

Defined by `Marwa\Framework\Config\HttpConfig`.

Supported keys:

- `enabled`: enable the shared HTTP client wrapper
- `default`: default client profile name
- `clients`: named client profiles keyed by profile name
- `clients.*.base_uri`: base URI for that client profile
- `clients.*.timeout`: request timeout in seconds
- `clients.*.connect_timeout`: connection timeout in seconds
- `clients.*.http_errors`: let Guzzle throw on 4xx/5xx responses
- `clients.*.verify`: TLS verification flag or CA path
- `clients.*.headers`: default request headers for that profile

Example:

```php
return [
    'enabled' => true,
    'default' => 'github',
    'clients' => [
        'github' => [
            'base_uri' => 'https://api.github.com',
            'timeout' => 15,
            'connect_timeout' => 5,
            'http_errors' => false,
            'verify' => true,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
            ],
        ],
    ],
];
```

## `config/notification.php`

Defined by `Marwa\Framework\Config\NotificationConfig`.

Supported keys:

- `enabled`: enable the notification manager
- `default`: default channel list used when a notification does not declare channels
- `channels.mail.enabled`: enable or disable mail delivery
- `channels.database.enabled`: enable or disable database persistence
- `channels.database.connection`: `marwa-db` connection name
- `channels.database.table`: database notifications table name
- `channels.http.enabled`: enable outbound HTTP/webhook delivery
- `channels.http.client`: HTTP client profile name
- `channels.http.method`: HTTP method such as `POST`
- `channels.http.url`: default webhook URL
- `channels.http.headers`: default HTTP headers
- `channels.sms.enabled`: enable SMS delivery
- `channels.sms.client`: HTTP client profile name used by the SMS gateway
- `channels.sms.method`: HTTP method for the SMS gateway
- `channels.sms.url`: SMS gateway URL
- `channels.kafka.enabled`: enable Kafka publishing
- `channels.kafka.publisher`: service id implementing `Marwa\Framework\Contracts\KafkaPublisherInterface`
- `channels.kafka.consumer`: service id implementing `Marwa\Framework\Contracts\KafkaConsumerInterface`
- `channels.kafka.topic`: default Kafka topic
- `channels.kafka.topics`: default Kafka topic list for consumers
- `channels.kafka.groupId`: default Kafka consumer group ID
- `channels.kafka.key`: optional Kafka message key
- `channels.kafka.headers`: Kafka record headers
- `channels.kafka.options`: extra publisher options
- `channels.broadcast.enabled`: enable event broadcast dispatching
- `channels.broadcast.event`: broadcast event class name

Example:

```php
return [
    'enabled' => true,
    'default' => ['mail', 'database'],
    'channels' => [
        'http' => [
            'enabled' => true,
            'url' => 'https://hooks.example.test/notify',
        ],
        'kafka' => [
            'enabled' => true,
            'publisher' => App\Kafka\MarwaKafkaPublisher::class,
            'consumer' => App\Kafka\MarwaKafkaConsumer::class,
            'topic' => 'notifications',
            'topics' => ['notifications'],
            'groupId' => 'app',
        ],
        'sms' => [
            'enabled' => true,
            'url' => 'https://sms.example.test/send',
        ],
    ],
];
```

## `config/cache.php`

Defined by `Marwa\Framework\Config\CacheConfig`.

Supported keys:

- `enabled`: enable the framework cache service
- `driver`: `sqlite`, `memory`, or `apcu`
- `namespace`: collection/prefix namespace for application keys
- `buffered`: enable Scrapbook local buffering
- `transactional`: wrap the store in Scrapbook transactions
- `stampede.enabled`: enable Scrapbook stampede protection
- `stampede.sla`: protection window in milliseconds
- `sqlite.path`: SQLite cache database path
- `sqlite.table`: cache table name
- `memory.limit`: optional in-memory store limit

Example:

```php
return [
    'enabled' => true,
    'driver' => 'sqlite',
    'namespace' => 'app',
    'buffered' => true,
    'transactional' => false,
    'stampede' => [
        'enabled' => true,
        'sla' => 1000,
    ],
    'sqlite' => [
        'path' => storage_path('cache/framework.sqlite'),
        'table' => 'framework_cache',
    ],
];
```

## `config/error.php`

Defined by `Marwa\Framework\Config\ErrorConfig`.

Supported keys:

- `enabled`: register the global PHP error, exception, and shutdown handlers
- `appName`: application name shown in fallback output and logs
- `environment`: environment passed to `marwa-error-handler`
- `useLogger`: forward uncaught errors to the shared PSR-3 logger
- `useDebugReporter`: report uncaught exceptions to the debug bar when available
- `renderer`: custom `Marwa\ErrorHandler\Contracts\RendererInterface` implementation

Example:

```php
return [
    'enabled' => true,
    'appName' => 'My App',
    'environment' => env('APP_ENV', 'production'),
    'useLogger' => true,
    'useDebugReporter' => true,
    'renderer' => Marwa\ErrorHandler\Support\FallbackRenderer::class,
];
```

## `config/security.php`

Defined by `Marwa\Framework\Config\SecurityConfig`.

Supported keys:

- `enabled`: master toggle for the security layer
- `csrf.enabled`: enable CSRF validation for unsafe HTTP methods
- `csrf.field`: hidden form field name used by `csrf_field()`
- `csrf.header`: header name checked by the middleware
- `csrf.token`: session key used to store the CSRF token
- `csrf.methods`: HTTP methods subject to CSRF checks
- `csrf.except`: list of path patterns exempt from CSRF
- `trustedHosts`: list of trusted hostnames or wildcard patterns
- `trustedOrigins`: list of trusted origin URLs or wildcard patterns
- `throttle.enabled`: enable cache-backed request throttling
- `throttle.prefix`: cache key prefix for throttle counters
- `throttle.limit`: request limit per window
- `throttle.window`: throttle window in seconds
- `risk.enabled`: enable security risk journaling
- `risk.logPath`: JSONL journal file for risk signals
- `risk.pruneAfterDays`: default retention window in days
- `risk.topCount`: number of latest signals shown in reports

Example:

```php
return [
    'enabled' => true,
    'csrf' => [
        'enabled' => true,
        'except' => ['webhook/*'],
    ],
    'trustedHosts' => ['example.com'],
    'trustedOrigins' => ['https://example.com'],
    'throttle' => [
        'enabled' => true,
        'prefix' => 'security',
        'limit' => 60,
        'window' => 60,
    ],
    'risk' => [
        'enabled' => true,
        'logPath' => storage_path('security/risk.jsonl'),
        'pruneAfterDays' => 30,
        'topCount' => 10,
    ],
];
```

## `config/database.php`

Defined by `Marwa\Framework\Config\DatabaseConfig`.

Supported keys:

- `enabled`: enable shared `marwa-db` bootstrap
- `default`: default connection name
- `connections`: named connection map in the `marwa-db` package format
- `debug`: default debug flag applied to connections unless overridden
- `migrationsPath`: application migrations directory
- `seedersPath`: application seeders directory
- `seedersNamespace`: namespace used by seeder discovery and scaffolding

The debug panel toggle lives in `config/app.php` as `useDebugPanel`.

Example:

```php
return [
    'enabled' => true,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('database/database.sqlite'),
            'debug' => false,
        ],
    ],
    'migrationsPath' => base_path('database/migrations'),
    'seedersPath' => base_path('database/seeders'),
    'seedersNamespace' => 'Database\\Seeders',
];
```

## `config/bootstrap.php`

Defined by `Marwa\Framework\Config\BootstrapConfig`.

Supported keys:

- `configCache`: full path to cached merged config output
- `routeCache`: full path to compiled route cache output
- `moduleCache`: full path to module manifest cache output

Default paths:

- `bootstrap/cache/config.php`
- `bootstrap/cache/routes.php`
- `bootstrap/cache/modules.php`

## `config/session.php`

Defined by `Marwa\Framework\Config\SessionConfig`.

Supported keys:

- `enabled`: enable the framework session service
- `autoStart`: start the session automatically through middleware on every HTTP request
- `name`: cookie/session name
- `lifetime`: session lifetime in seconds
- `path`: cookie path
- `domain`: cookie domain
- `secure`: mark the cookie as HTTPS-only
- `httpOnly`: prevent JavaScript access to the cookie
- `sameSite`: cookie `SameSite` value such as `Lax`, `Strict`, or `None`
- `encrypt`: encrypt stored session payloads with `APP_KEY`

Example:

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

Encrypted sessions require `APP_KEY` to be configured.

The session service also supports flash data through `flash()`, `now()`, `reflash()`, and `keep()`.

## `config/schedule.php`

Defined by `Marwa\Framework\Config\ScheduleConfig`.

Supported keys:

- `enabled`: enable the scheduler runtime
- `driver`: `file`, `cache`, or `database`
- `lockPath`: legacy alias for `file.path`
- `file.path`: directory used for file-based lock and state records
- `cache.namespace`: cache key prefix used for scheduler state and locks
- `database.connection`: `marwa-db` connection name used by the scheduler store
- `database.table`: table used for scheduler state and overlap locks
- `defaultLoopSeconds`: default `schedule:run --for` duration
- `defaultSleepSeconds`: default `schedule:run --sleep` duration

Example:

```php
return [
    'enabled' => true,
    'driver' => 'cache',
    'file' => [
        'path' => storage_path('framework/schedule'),
    ],
    'cache' => [
        'namespace' => 'schedule',
    ],
    'database' => [
        'connection' => 'sqlite',
        'table' => 'schedule_jobs',
    ],
    'defaultLoopSeconds' => 60,
    'defaultSleepSeconds' => 1,
];
```

Use `php marwa schedule:table` to create a migration stub for the configured scheduler table when the database driver is enabled.

## `config/storage.php`

Defined by `Marwa\Framework\Config\StorageConfig`.

Supported keys:

- `default`: default storage disk name
- `disks`: configured disk definitions
- `disks.<name>.driver`: currently `local`
- `disks.<name>.root`: root directory for the disk
- `disks.<name>.visibility`: default Flysystem visibility

Example:

```php
return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'visibility' => 'private',
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
        ],
    ],
];
```

## `config/module.php`

Defined by `Marwa\Framework\Config\ModuleConfig`.

Supported keys:

- `enabled`: enable module-aware starter behavior
- `paths`: list of module root directories
- `cache`: full path to the module manifest cache file
- `forceRefresh`: ignore the module cache and rescan the filesystem
- `commandPaths`: manifest `paths` keys that should be treated as command directories
- `commandConventions`: module-relative fallback directories for command discovery

## `config/queue.php`

Defined by `Marwa\Framework\Config\QueueConfig`.

Supported keys:

- `enabled`: enable the shared file-backed queue
- `default`: default queue name
- `path`: queue storage root
- `retryAfter`: retry visibility timeout in seconds for user-defined workers

Example:

```php
return [
    'enabled' => true,
    'default' => 'default',
    'path' => storage_path('queue'),
    'retryAfter' => 90,
];
```

## `config/schedule.php`

Defined by `Marwa\Framework\Config\ScheduleConfig`.

Supported keys:

- `enabled`: enable the scheduler runtime
- `lockPath`: directory for overlap-prevention lock files
- `defaultLoopSeconds`: default `schedule:run --for` value
- `defaultSleepSeconds`: default `schedule:run --sleep` value

Example:

```php
return [
    'enabled' => true,
    'lockPath' => storage_path('framework/schedule'),
    'defaultLoopSeconds' => 60,
    'defaultSleepSeconds' => 1,
];
```

Example:

```php
return [
    'enabled' => true,
    'paths' => [
        base_path('modules'),
    ],
    'cache' => base_path('bootstrap/cache/modules.php'),
    'forceRefresh' => false,
    'commandPaths' => ['commands'],
    'commandConventions' => ['Console/Commands', 'src/Console/Commands'],
];
```
