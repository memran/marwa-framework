# Starter Kits

Marwa Framework is a minimal core framework. There are currently no official starter kits, but you can quickly bootstrap an application manually.

## Manual Setup

### 1. Create Project Directory

```bash
mkdir myapp && cd myapp
```

### 2. Install Framework

```bash
composer require memran/marwa-framework
```

### 3. Create Basic Structure

```
myapp/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Providers/
├── config/
│   ├── app.php
│   ├── database.php
│   └── session.php
├── public/
│   └── index.php
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
├── bootstrap/
└── .env
```

### 4. Create Entry Point

```php
// public/index.php
<?php

use Marwa\Framework\HttpKernel;

require __DIR__ . '/../vendor/autoload.php';

$kernel = new HttpKernel(dirname(__DIR__));
$response = $kernel->handle();

$response->send();
$kernel->terminate($response);
```

### 5. Configure Routes

```php
// routes/web.php
use Marwa\Router\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/about', function () {
    return view('about');
});
```

### 6. Create View

```php
// resources/views/home.twig
<h1>Welcome to Marwa</h1>
```

## Module-Based Development

For larger applications, use modules:

```bash
php marwa make:module Blog
```

This creates a `modules/Blog/` directory with:

```
modules/Blog/
├── manifest.php
├── BlogServiceProvider.php
├── Controllers/
├── Models/
├── Resources/
│   └── views/
└── database/
    └── migrations/
```

## Minimal Requirements

- PHP 8.2+
- Composer
- Web server (Apache/Nginx)
- PDO extension (for database)

## What's Next?

- Read the [Getting Started](/docs/getting-started/index.md) guide
- Explore [Routing](/docs/basics/routing.md)
- Learn about [Middleware](/docs/basics/middleware.md)
- Set up [Configuration](/docs/getting-started/configuration.md)
