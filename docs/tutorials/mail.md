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

## Queue a message

```php
use App\Mail\WelcomeMail;

(new WelcomeMail([
    'subject' => 'Welcome',
    'to' => ['user@example.com' => 'User'],
    'html' => '<p>Welcome to MarwaPHP.</p>',
]))->queue('mail');
```

Queued mail jobs are stored as `mail:send` entries in the shared file queue. A worker can later hydrate the payload and call `Marwa\Framework\Queue\MailJob::handle()`.

## Attach files

```php
mailer()
    ->to('user@example.com')
    ->subject('Report')
    ->text('See the attached file.')
    ->attach(storage_path('reports/daily.csv'), 'daily.csv', 'text/csv')
    ->send();
```
