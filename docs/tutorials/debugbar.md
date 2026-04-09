# DebugBar

DebugBar provides debugging and profiling information during development.

## Configuration

Enable DebugBar via `config/app.php`:

```php
// config/app.php
return [
    // ...
    'debugbar' => env('DEBUGBAR_ENABLED', false),
];
```

Or in your `.env` file:

```env
DEBUGBAR_ENABLED=true
```

## Usage

Get the DebugBar instance using the helper function:

```php
$bar = debugger();

if ($bar !== null) {
    // DebugBar is enabled
}
```

## Logging Messages

Log messages at different levels:

```php
$bar = debugger();

if ($bar !== null) {
    $bar->log('info', 'User logged in', ['user_id' => 123]);
    $bar->log('warning', 'Slow query detected');
    $bar->log('error', 'Something went wrong');
}
```

**Supported log levels:** `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`

## Dumping Variables

Inspect variables in the DebugBar:

```php
$bar = debugger();

if ($bar !== null) {
    $bar->addDump($variable);
    $bar->addDump($user, 'Current User');
    $bar->addDump($array, 'Request Data', __FILE__, __LINE__);
}
```

## Timing

Mark points in your code to measure execution time:

```php
$bar = debugger();

if ($bar !== null) {
    $bar->mark('Start of process');
}

// ... your code ...

if ($bar !== null) {
    $bar->mark('End of process');
}
```

## Database Queries

Record database queries with timing information:

```php
$bar = debugger();

if ($bar !== null) {
    $bar->addQuery(
        sql: "SELECT * FROM users WHERE id = ?",
        params: [1],
        durationMs: 0.523,
        connection: 'mysql'
    );
}
```

## Exceptions

Track exceptions:

```php
$bar = debugger();

try {
    // code that throws
} catch (\Exception $e) {
    if ($bar !== null) {
        $bar->addException($e);
    }
}
```

### Auto-capture All Exceptions

Register exception handlers to automatically capture all unhandled exceptions:

```php
$bar = debugger();

if ($bar !== null) {
    $bar->registerExceptionHandlers();
}
```

## Example: Controller Debugging

```php
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

class UserController extends Controller
{
    public function index(): ResponseInterface
    {
        $bar = debugger();
        
        if ($bar !== null) {
            $bar->mark('Controller started');
            $bar->log('info', 'Loading users');
        }
        
        $users = User::all();
        
        if ($bar !== null) {
            $bar->addDump($users->toArray(), 'Users');
            $bar->mark('Controller finished');
        }
        
        return view('users.index', ['users' => $users]);
    }
}
```

## Display

The DebugBar automatically injects itself into HTML responses before the closing `</body>` tag when:

1. DebugBar is enabled in config
2. The response Content-Type is `text/html`
3. The response body contains a `</body>` tag

## Notes

- DebugBar should be **disabled in production**
- The `debugger()` helper returns `null` when DebugBar is disabled (safe to use without conditionals in production)
- Maximum 100 dumps are stored to prevent memory issues
