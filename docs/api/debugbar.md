# DebugBar API Reference

## Configuration

```php
// config/app.php
'debugbar' => env('DEBUGBAR_ENABLED', false),
```

## Helper Function

```php
function debugger(): mixed
```

Returns the `DebugBar` instance if enabled, `null` otherwise.

## DebugBar Methods

### `enable(): void`

Enable the DebugBar.

```php
$bar->enable();
```

### `disable(): void`

Disable the DebugBar.

```php
$bar->disable();
```

### `isEnabled(): bool`

Check if DebugBar is enabled.

```php
if ($bar->isEnabled()) {
    // DebugBar is active
}
```

### `mark(string $label): void`

Record a timing mark.

```php
$bar->mark('Start of process');
$bar->mark('End of process');
```

### `log(string $level, string $message, array $context = []): void`

Log a message at the specified level.

```php
$bar->log('info', 'User logged in', ['user_id' => 123]);
$bar->log('warning', 'Slow query detected');
$bar->log('error', 'Something went wrong');
```

**Parameters:**
- `$level` - Log level: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`
- `$message` - The log message
- `$context` - Optional context data

### `addDump(mixed $value, ?string $name = null, ?string $file = null, ?int $line = null): void`

Add a variable dump to the DebugBar.

```php
$bar->addDump($variable);
$bar->addDump($user, 'Current User');
$bar->addDump($data, 'Debug', __FILE__, __LINE__);
```

**Parameters:**
- `$value` - The variable to dump
- `$name` - Optional label for the dump
- `$file` - Optional source file path
- `$line` - Optional source line number

### `addQuery(string $sql, array $params = [], float $durationMs = 0.0, ?string $connection = null): void`

Record a database query.

```php
$bar->addQuery(
    sql: "SELECT * FROM users WHERE id = ?",
    params: [1],
    durationMs: 0.523,
    connection: 'mysql'
);
```

**Parameters:**
- `$sql` - The SQL query
- `$params` - Query parameters
- `$durationMs` - Query duration in milliseconds
- `$connection` - Database connection name

### `addException(Throwable $exception): void`

Record an exception.

```php
try {
    // code
} catch (\Exception $e) {
    $bar->addException($e);
}
```

### `registerExceptionHandlers(bool $capturePhpErrorsAsExceptions = true): void`

Register global exception handlers to auto-capture all exceptions.

```php
$bar->registerExceptionHandlers();
// Captures: uncaught exceptions, PHP errors, fatal errors
```

**Parameters:**
- `$capturePhpErrorsAsExceptions` - Capture PHP errors as exceptions (default: true)

### `setLogger(LoggerInterface $logger): void`

Set a PSR-3 logger to forward log messages.

```php
$bar->setLogger($psrLogger);
```

### `setMaxDumps(int $maxDumps): void`

Set maximum number of dumps to store.

```php
$bar->setMaxDumps(50);
```

### `elapsedMilliseconds(): float`

Get elapsed time since request start in milliseconds.

```php
$elapsed = $bar->elapsedMilliseconds();
```

### `collectors(): CollectorManager`

Access the collector manager.

```php
$managers = $bar->collectors();
```
