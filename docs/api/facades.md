# Facade API

The framework includes lightweight static facades that resolve services from the application container.

## `Router`

```php
use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/ping', fn () => Response::json(['pong' => true]))->register();
```

Short description:
Provides fluent route registration through the router adapter.

## `Config`

```php
use Marwa\Framework\Facades\Config;

$debug = Config::getBool('app.debug', false);
```

Short description:
Reads typed configuration values from loaded config files.

## `Event`

```php
use Marwa\Framework\Facades\Event;

Event::listen(UserRegistered::class, SendWelcomeMail::class, 100);
Event::dispatch(new UserRegistered('user@example.com'));
```

Short description:
Dispatches object events and registers prioritized listeners through the configured `marwa-event` bus.

## `Log`

```php
use Marwa\Framework\Facades\Log;

Log::info('Application started.');
```

Short description:
Writes structured logs through the framework logger adapter.

## `Mailer`

```php
use Marwa\Framework\Facades\Mailer;

Mailer::to('user@example.com')
    ->subject('Welcome')
    ->text('Hello from MarwaPHP.')
    ->send();
```

Short description:
Builds and sends messages through the shared SwiftMailer-compatible mail service.

## `Notification`

```php
use Marwa\Framework\Facades\Notification;

Notification::send(new App\Notifications\OrderShipped(), $user);
```

Short description:
Dispatches a notification through the configured channel manager.

## `View`

```php
use Marwa\Framework\Facades\View;

return View::render('welcome', ['name' => 'Marwa']);
```

Short description:
Renders templates through the view adapter.
