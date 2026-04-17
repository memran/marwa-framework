# Session Management

Marwa Framework provides encrypted session management with automatic lifecycle handling, flash data support, and secure cookie configuration.

## Configuration

Sessions are configured in `config/session.php`:

```php
return [
    'enabled' => true,
    'autoStart' => false,        // Manual start by default
    'name' => 'marwa_session',   // Cookie name
    'lifetime' => 7200,          // 2 hours in seconds
    'path' => '/',
    'domain' => '',
    'secure' => true,            // HTTPS only in production
    'httpOnly' => true,          // No JavaScript access
    'sameSite' => 'Lax',
    'encrypt' => true,           // Encrypt session data
];
```

> **Important**: Set `APP_KEY` in your `.env` file when using encrypted sessions.

## Basic Usage

### Starting a Session

```php
use Marwa\Framework\Contracts\SessionInterface;

// Auto-injected or via app container
$session = app(SessionInterface::class);
$session->start();
```

### Setting and Getting Values

```php
$session->set('user_id', 123);
$session->set('preferences', ['theme' => 'dark']);

// Retrieve values
$userId = $session->get('user_id');           // 123
$preferences = $session->get('preferences'); // ['theme' => 'dark']

// With default value
$missing = $session->get('nonexistent', 'default'); // 'default'
```

### Checking if Key Exists

```php
if ($session->has('user_id')) {
    // Key exists
}
```

### Removing Data

```php
$session->forget('user_id'); // Remove single key
$session->flush();          // Clear all session data
```

## Helper Functions

### Using the `session()` Helper

```php
// Get all sessions
$all = session()->all();

// Set a value
session()->set('key', 'value');

// Get a value
$value = session('key', 'default');

// Shorthand for set
session(['key' => 'value', 'another' => 'data']);
```

## Flash Data

Flash data persists only for the next request, ideal for success messages:

```php
// Set flash data
$session->flash('success', 'Profile updated successfully!');

// Available on next request only
$message = session()->get('success');

// Keep flash data for another request
session()->keep(['success']);

// Keep all flash data
session()->reflash();
```

### Flash Now

Immediate flash data (available this request AND next):

```php
$session->now('status', 'Processing...');
```

## Session ID and Regeneration

### Getting the Session ID

```php
$id = session()->id();
```

### Regenerating the Session ID

```php
// Regenerate without destroying data
session()->regenerate();

// Regenerate and destroy old session
session()->regenerate(destroy: true);
```

### Invalidating (Logout)

```php
session()->invalidate(); // Clears all data and destroys session
```

## Auto-Start Sessions

Enable automatic session start:

```php
// config/session.php
return [
    'autoStart' => true,
];
```

Or start manually when needed:

```php
$session->start();
```

## Security Features

### Encryption

Session data is encrypted using AES-256-GCM when `encrypt` is enabled:

```php
// Requires APP_KEY in .env
APP_KEY=base64:your-32-byte-key-here
```

### Cookie Security

| Setting | Default | Description |
|---------|---------|-------------|
| `httpOnly` | `true` | Prevents JavaScript access |
| `secure` | `true` in production | HTTPS only |
| `sameSite` | `Lax` | CSRF protection |

## Closing Sessions

Close the session after processing:

```php
session()->close(); // Saves data and ends write lock
```

This is handled automatically by the `SessionMiddleware`.

## Complete Example

```php
// In a controller
public function login(Request $request)
{
    $user = $this->authenticate($request);

    session()->regenerate();
    session()->set('user_id', $user->id);
    session()->flash('success', 'Welcome back!');

    return redirect('/dashboard');
}

public function logout()
{
    session()->invalidate();
    return redirect('/login');
}
```

## Flash Data Flow

```
Request 1: flash('message', 'Saved!')     → Message stored
Request 2: get('message') returns 'Saved!' → Message displayed
Request 3: get('message') returns null     → Message expired
```
