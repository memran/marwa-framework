# Queue Guide

This guide covers using the queue system for background job processing.

## Overview

The queue system allows you to defer expensive tasks to be processed later:

- Send emails asynchronously
- Process uploads
- Generate reports
- API callouts

## Configuration

### config/queue.php

```php
return [
    'enabled' => true,
    'default' => 'default',
    'path' => storage_path('queue'),
    'retryAfter' => 60,
];
```

## Creating Jobs

### Basic Job

```php
// app/Jobs/SendWelcomeEmail.php
<?php

declare(strict_types=1);

namespace App\Jobs;

use Marwa\Framework\Queue\QueuedJob;
use Marwa\Framework\Queue\MailJob;

final class SendWelcomeEmail implements MailJob
{
    public function __construct(
        private string $email,
        private string $name
    ) {}

    public function payload(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    public function handle(): void
    {
        // Send email
        mail($this->email, 'Welcome!', "Hi {$this->name}, welcome!");
    }
}
```

### Closure Job

```php
use Marwa\Framework\Facades\Queue;

Queue::push(function () {
    // Expensive operation
    logger()->info('Background job executed');
});
```

## Dispatching Jobs

### From Controller

```php
use Marwa\Framework\Facades\Queue;

public function store(Request $request): Response
{
    // Dispatch immediately
    Queue::push(new SendWelcomeEmail(
        email: $request->input('email'),
        name: $request->input('name')
    ));

    return response()->json(['message' => 'Queued']);
}
```

### With Delay

```php
// Delay for 5 minutes
Queue::push(new SendWelcomeEmail(...), delaySeconds: 300);
```

### To Specific Queue

```php
// Process in 'emails' queue
Queue::push(new SendWelcomeEmail(...), queue: 'emails');
```

## Processing Jobs

### Run Queue Worker

```bash
php marwa queue:work
```

### Process Specific Queue

```bash
php marwa queue:work --queue=emails
```

### Process All Queues

```bash
php marwa queue:work --queue=default,emails
```

## Queue Commands

| Command | Description |
|---------|-------------|
| `queue:work` | Process queued jobs |
| `queue:listen` | Listen for new jobs |
| `queue:retry` | Retry failed jobs |

### Listen Mode

```bash
# Keep running and process new jobs
php marwa queue:listen
```

### Retry Failed Jobs

```bash
php marwa queue:retry
```

## Monitoring

### Check Queue Status

```php
use Marwa\Framework\Facades\Queue;

$status = Queue::status();
```

### Job History

Jobs are stored in `storage/queue/` directory.

## Configuration Options

### config/queue.php

```php
return [
    'enabled' => true,
    'default' => 'default',
    'path' => storage_path('queue'),
    'retryAfter' => 60,
    
    'connections' => [
        'default' => [
            'driver' => 'file', // or 'sync' for immediate
        'path' => storage_path('queue/default'),
        'retryAfter' => 60,
        ],
        'emails' => [
            'driver' => 'file',
            'path' => storage_path('queue/emails'),
            'retryAfter' => 120,
        ],
    ],
];
```

## Error Handling

### Failed Job Callback

```php
// In a service provider
public function boot(): void
{
    Queue::failed(function (QueuedJob $job, \Throwable $e) {
        logger()->error('Job failed', [
            'job' => $job->name(),
            'error' => $e->getMessage(),
        ]);
    });
}
```

## Best Practices

### 1. Keep Jobs Small

```php
// Good - delegate to service
public function handle(): void
{
    $service = app(NotificationService::class);
    $service->sendWelcome($this->email);
}

// Bad - too much logic
public function handle(): void
{
    // Complex processing...
}
```

### 2. Use Idempotent Jobs

```php
public function handle(): void
{
    // Check if already processed
    if ($this->isAlreadyProcessed()) {
        return;
    }
    // Process...
}
```

### 3. Queue-Specific Processing

```php
// High priority - process immediately
Queue::push(new ProcessPayment(...), queue: 'payments');

// Low priority - batch later
Queue::push(new GenerateReport(...), queue: 'reports');
```

## Troubleshooting

### Jobs Not Processing

1. Check queue is enabled in config
2. Ensure storage is writable
3. Run worker: `php marwa queue:work`

### All Jobs Fail

Check failed job logs in `storage/logs/`

## Related

- [Console Commands](console/index.md) - CLI reference
- [Mail Tutorial](../tutorials/mail.md) - Sending emails
- [Scheduling](scheduling.md) - Scheduled tasks