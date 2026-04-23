# Mail Tutorial

The framework exposes a SwiftMailer-compatible `mailer()` service for SMTP, sendmail, or native mail transports.

## Configure `config/mail.php`

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

## Send a message

```php
mailer()
    ->to('user@example.com', 'User')
    ->cc('copy@example.com')
    ->subject('Welcome')
    ->html('<p>Welcome to MarwaPHP.</p>', 'Welcome to MarwaPHP.')
    ->send();
```

## Send HTML from Twig template

```php
mailer()
    ->to('user@example.com')
    ->subject('Welcome')
    ->htmlTemplate('emails.welcome', ['name' => 'John'])
    ->send();
```

## Queue a message

### Basic Queueing with Delay

```php
use App\Mail\WelcomeMail;

(new WelcomeMail([
    'subject' => 'Welcome',
    'to' => ['user@example.com' => 'User'],
    'html' => '<p>Welcome to MarwaPHP.</p>',
]))->queue('mail', 3600); // Delay 1 hour
```

### Queue at Specific Time

```php
$tomorrow = strtotime('tomorrow 3pm');
(new WelcomeMail($data))->queueAt('high-priority', $tomorrow);
```

### Recurring Emails

```php
(new DailyReport($data))->queueRecurring('reports', [
    'expression' => '0 9 * * *', // Daily at 9 AM
    'timezone' => 'America/New_York'
]);
```

### Priority Queues

```php
// High priority
(new UrgentNotification($data))->queue('urgent');

// Bulk emails
(new Newsletter($data))->queue('bulk');
```

Queued mail jobs are stored as `mail:send` entries in the shared file queue. A worker can later hydrate the payload and call `Marwa\Framework\Queue\MailJob::handle()`.

## Attach files

### Attach from filesystem

```php
mailer()
    ->to('user@example.com')
    ->subject('Report')
    ->text('See the attached file.')
    ->attach('/path/to/file.pdf', 'report.pdf', 'application/pdf')
    ->send();
```

### Attach from storage

```php
mailer()
    ->to('user@example.com')
    ->subject('Report')
    ->text('See the attached file.')
    ->attachFromStorage('reports/daily.pdf', 'daily-report.pdf', 's3')
    ->send();
```
