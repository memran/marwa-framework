# Scheduling Guide

This guide covers scheduling recurring tasks using the scheduler.

## Overview

The scheduler lets you schedule tasks to run at specific intervals:

- Daily database backups
- Weekly reports
- Hourly cleanup tasks
- Minute-long health checks

## Quick Start

### 1. Create Scheduled Task

```php
// app/Console/Kernel.php
<?php

declare(strict_types=1);

namespace App\Console;

use Marwa\Framework\Scheduling\Scheduler;

final class Kernel
{
    public function schedule(Scheduler $scheduler): void
    {
        // Run every minute
        $scheduler->command('logs:clean')->everyMinute();
        
        // Run daily at midnight
        $scheduler->command('backup:run')->daily();
        
        // Run every hour
        $scheduler->command('reports:generate')->hourly();
    }
}
```

### 2. Run Scheduler

```bash
# Run scheduler
php marwa schedule:run
```

`schedule:run` is persistent by default. Use `--once` for a single scheduler tick, or `--for=60 --sleep=1` when cron should keep the scheduler alive for one minute.

### 3. Setup Cron (Production)

```bash
# crontab -e
* * * * * /usr/bin/php /var/www/yourapp marwa schedule:run --for=60 --sleep=1 >> /dev/null 2>&1
```

## Scheduling Methods

### Basic Schedules

```php
use Marwa\Framework\Scheduling\Scheduler;

public function schedule(Scheduler $scheduler): void
{
    // Every minute
    $scheduler->command('task')->everyMinute();
    
    // Every 5 minutes
    $scheduler->command('task')->everyFiveMinutes();
    
    // Every 10 minutes
    $scheduler->command('task')->everyTenMinutes();
    
    // Every 30 minutes
    $scheduler->command('task')->everyThirtyMinutes();
    
    // Hourly
    $scheduler->command('task')->hourly();
    
    // Daily at midnight
    $scheduler->command('task')->daily();
    
    // Daily at specific time
    $scheduler->command('task')->dailyAt('08:00');
    
    // Weekly
    $scheduler->command('task')->weekly();
    
    // Monthly
    $scheduler->command('task')->monthly();
}
```

### Cron-Like Schedules

```php
// Every day at 8am
$scheduler->command('task')->cron('0 8 * * *');

// Every Monday at 9am
$scheduler->command('task')->cron('0 9 * * 1');

// Every 15th of month at midnight
$scheduler->command('task')->cron('0 0 15 * *');
```

### Schedule Constraints

```php
// Run on weekdays only
$scheduler->command('task')
    ->weekdays()
    ->daily();

// Run on specific days
$scheduler->command('task')
    ->sundays()
    ->daily();

// Run between times
$scheduler->command('task')
    ->between('08:00', '18:00')
    ->hourly();

// Skip in maintenance mode
$scheduler->command('task')->withoutOverlapping();
```

## Task Types

### CLI Commands

```php
// Schedule built-in command
$scheduler->command('cache:clear')->daily();

// Schedule custom command
$scheduler->command('reports:generate')->daily();

// With arguments
$scheduler->command('user:cleanup --force')->weekly();
```

### Closures

```php
// Schedule closure
$scheduler->call(function () {
    // Clean old sessions
    DB::table('sessions')
        ->where('last_activity', '<', now()->subDay())
        ->delete();
})->daily();
```

### Shell Commands

```php
// Schedule shell command
$scheduler->exec('git pull origin main')->daily();

// With multiple commands
$scheduler->exec([
    'composer install --no-dev',
    'php marwa migrate --force',
])->hourly();
```

## Preventing Overlap

### Without Overlapping

```php
// Prevent task from running if still running
$scheduler->command('import:users')
    ->withoutOverlapping()
    ->hourly();

// With expiry (default 1440 minutes = 24 hours)
$scheduler->command('import:users')
    ->withoutOverlapping(60)
    ->hourly();
```

### Run on One Server

```php
// Only run on single server in cluster
$scheduler->command('backup:run')
    ->onOneServer()
    ->daily();
```

## Maintenance Mode

```php
// Skip in maintenance mode (default)
$scheduler->command('task')->daily();

// Run even in maintenance mode
$scheduler->command('task')
    ->evenInMaintenanceMode()
    ->daily();
```

## Task Persistence

### Using Database

```bash
# Create schedule table
php marwa schedule:table

# This creates database/schedule.php
```

### Using Cache

```php
// config/queue.php or config/cache.php
return [
    'driver' => 'redis',
    // ...
];
```

## Monitoring

### List Scheduled Tasks

```bash
php marwa schedule:list
```

Output:
```
+------------------------------------------------+------------+
| Command                                        | Interval   |
+------------------------------------------------+------------+
| logs:clean                                     | Every 1m  |
| backup:run                                    | Daily     |
| reports:generate                              | Hourly    |
+------------------------------------------------+------------+
```

## Examples

### Daily Database Backup

```php
$scheduler->command('db:backup')
    ->dailyAt('02:00')
    ->withoutOverlap()
    ->appendOutputTo(storage_path('logs/backup.log'));
```

### Hourly Report Generation

```php
$scheduler->command('reports:generate hourly')
    ->hourly()
    ->withoutOverlap();

// Also daily summary
$scheduler->command('reports:generate daily')
    ->dailyAt('23:00')
    ->withoutOverlap();
```

### Cleanup Old Data

```php
$scheduler->call(function () {
    DB::table('sessions')
        ->where('last_activity', '<', now()->subDays(30))
        ->delete();
    
    DB::table('password_resets')
        ->where('created_at', '<', now()->subDays(7))
        ->delete();
})->dailyAt('03:00');
```

### Health Check

```php
$scheduler->call(function () {
    $status = checkDatabase() && checkCache() && checkQueue();
    
    DB::table('health_checks')->insert([
        'status' => $status ? 'ok' : 'failed',
        'checked_at' => now(),
    ]);
})->everyFiveMinutes();
```

## Console Commands

| Command | Description |
|---------|-------------|
| `schedule:run` | Run due scheduled tasks |
| `schedule:list` | List all scheduled tasks |
| `schedule:table` | Create schedule table |

## Configuration

### config/schedule.php

```php
return [
    'store' => 'database', // or 'cache'
];
```

## Troubleshooting

### Tasks Not Running

1. Verify cron is set up:
```bash
crontab -l
```

2. Check scheduler is running:
```bash
php marwa schedule:list
```

3. Check logs:
```bash
tail storage/logs/app.log
```

### Tasks Running Multiple Times

Use `withoutOverlapping()`:

```php
$scheduler->command('task')->withoutOverlap()->everyMinute();
```

## Related

- [Queue Tutorial](queue.md) - Background jobs
- [Console Commands](console/index.md) - CLI reference
- [Deployment](../recipes/deployment.md) - Production cron setup
