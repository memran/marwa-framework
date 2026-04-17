# Logging

Marwa Framework uses PSR-3 compatible logging with support for multiple channels, sensitive data filtering, and structured log formatting.

## Configuration

Logging is configured in `config/logger.php`:

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

## Log Levels

Available log levels (from most to least severe):

| Level | Description |
|-------|-------------|
| `emergency` | System is unusable |
| `alert` | Immediate action required |
| `critical` | Critical conditions |
| `error` | Error conditions |
| `warning` | Warning conditions |
| `notice` | Normal but significant |
| `info` | Informational messages |
| `debug` | Debug-level messages |

## Basic Usage

### Using the Logger

```php
use Psr\Log\LoggerInterface;

// Via dependency injection
class UserController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function store(Request $request)
    {
        $this->logger->info('User created', [
            'user_id' => $user->id,
            'email' => $request->get('email'),
        ]);
    }
}

// Via helper function
logger()->error('Something went wrong', [
    'exception' => $e->getMessage(),
]);
```

## Log Channels

Configure different storage drivers:

```php
// config/logger.php
'storage' => [
    'driver' => 'file',        // file, daily, syslog, errorlog, null
    'path' => storage_path('logs'),
    'prefix' => 'marwa',
],
```

### Available Drivers

- **file**: Single log file
- **daily**: Daily rotating files
- **syslog**: System logger
- **errorlog**: PHP error log
- **null**: Discards all logs

## Sensitive Data Filtering

Automatically filter sensitive values from logs:

```php
// config/logger.php
'filter' => [
    'password',
    'password_confirmation',
    'secret',
    'token',
    'api_key',
    'credit_card',
],
```

Filtered values are replaced with `[Filtered]` in logs.

## Contextual Logging

### Adding Context

```php
logger()->info('User logged in', [
    'user_id' => $user->id,
    'ip' => request()->getClientIp(),
    'user_agent' => request()->getHeader('User-Agent'),
]);
```

### Exception Logging

```php
try {
    // code
} catch (\Exception $e) {
    logger()->error('Operation failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

## Log File Locations

Default log locations:

```
storage/logs/
├── marwa.log          # Default log file
├── marwa-2026-01-01.log  # Daily logs (when using daily driver)
```

## Environment-Based Logging

### Development

```env
APP_DEBUG=true
LOG_CHANNEL=file
```

### Production

```env
APP_DEBUG=false
LOG_CHANNEL=file
```

## Disabling Logging

```php
// config/logger.php
'enable' => false,
```

## SimpleLogger Interface

The framework uses `Marwa\Logger\SimpleLogger` which provides:

```php
logger()->emergency('System down');
logger()->alert('Database connection lost');
logger()->critical('File not found');
logger()->error('Operation failed');
logger()->warning('Deprecated method');
logger()->notice('User logged in');
logger()->info('Cache cleared');
logger()->debug('Query executed');
```

## Best Practices

1. **Use appropriate levels**: Don't log everything as `info`
2. **Include context**: Add relevant identifiers and metadata
3. **Filter sensitive data**: Add password, token, and secret fields
4. **Don't log in tight loops**: Batch or sample if needed
5. **Rotate logs**: Use daily driver in production
