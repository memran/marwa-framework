# Events

Marwa Framework dispatches lifecycle events through the shared `marwa-event` bus. You can listen to them from application code through `config/event.php` or by registering subscribers.

## Register Listeners In `config/event.php`

```php
<?php

use App\Listeners\LogApplicationBoot;
use App\Listeners\LogRequestHandled;
use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\Event\RequestHandled;

return [
    'listeners' => [
        AppBooted::class => [
            [LogApplicationBoot::class, 'handle'],
        ],
        RequestHandled::class => [
            [LogRequestHandled::class, 'handle'],
        ],
    ],
    'subscribers' => [],
];
```

Listener classes can be resolved from the container.

```php
<?php

namespace App\Listeners;

use Marwa\Framework\Adapters\Event\RequestHandled;

final class LogRequestHandled
{
    public function handle(RequestHandled $event): void
    {
        logger()->info('request_handled', [
            'method' => $event->method,
            'path' => $event->path,
            'status' => $event->statusCode,
        ]);
    }
}
```

## Register Subscribers

```php
<?php

namespace App\Listeners;

use Marwa\Event\Contracts\Subscriber;
use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\Event\ConsoleBootstrapped;

final class RuntimeSubscriber implements Subscriber
{
    public static function subscribe($events): void
    {
        $events->listen(AppBooted::class, [self::class, 'onBoot']);
        $events->listen(ConsoleBootstrapped::class, [self::class, 'onConsoleBoot']);
    }

    public static function onBoot(AppBooted $event): void {}

    public static function onConsoleBoot(ConsoleBootstrapped $event): void {}
}
```

Then add it to `config/event.php`:

```php
'subscribers' => [
    App\Listeners\RuntimeSubscriber::class,
],
```

## Available Lifecycle Events

- `ApplicationStarted`: fired after the core container and shared services are bound
- `ApplicationBootstrapping`: fired before app config and providers are bootstrapped
- `ProvidersBootstrapped`: fired after configured service providers are registered
- `ErrorHandlerBootstrapped`: fired after the shared error handler is initialized
- `ModulesBootstrapped`: fired when enabled modules are loaded
- `AppBooted`: fired once the shared app bootstrap completes
- `RequestHandlingStarted`: fired before the HTTP pipeline handles a request
- `RequestHandled`: fired after the HTTP pipeline returns a response
- `AppTerminated`: fired before the HTTP response is emitted
- `ConsoleBootstrapped`: fired after the Symfony Console application is assembled

## Manual Dispatch

For custom domain events, you can still dispatch directly:

```php
use Marwa\Framework\Facades\Event;

Event::dispatch(new UserRegistered($userId));
```
