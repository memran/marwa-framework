# Notifications Tutorial

## Overview

Notifications let one domain action fan out to multiple delivery channels: mail, database, HTTP, SMS, and broadcast. The framework provides a shared notification manager through `app()->notifications()`, the `notification()` helper, and the `Marwa\Framework\Facades\Notification` facade.

## Creating a Notification

Extend the base class and declare the channels plus per-channel payloads:

```php
use Marwa\Framework\Notifications\Notification;

final class OrderShipped extends Notification
{
    public function via(?object $notifiable = null): array
    {
        return ['mail', 'database', 'http', 'broadcast'];
    }

    public function toMail(?object $notifiable = null): array
    {
        return [
            'to' => $notifiable?->email,
            'subject' => 'Order shipped',
            'text' => 'Your order has shipped.',
        ];
    }
}
```

## Sending a Notification

```php
$user = new App\Models\User();
notification()->send(new App\Notifications\OrderShipped(), $user);
```

If your model uses `Marwa\Framework\Notifications\Notifiable`, you can call:

```php
$user->notify(new App\Notifications\OrderShipped());
```

## Channel Configuration

Use `config/notification.php` to enable the channels you need. Mail and database are useful defaults. HTTP and SMS are driven through the Guzzle client wrapper, so they fit webhook and provider APIs cleanly. Broadcast dispatches a framework event and can be observed through the existing event bus.

## Database Channel

The database channel stores notification rows in the configured table with a JSON payload. Create the table with your own migration and keep the shape simple: `notifiable_type`, `notifiable_id`, `type`, `channel`, `payload`, `read_at`, and `created_at`.

