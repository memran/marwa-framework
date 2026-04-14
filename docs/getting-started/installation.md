# Installation

This guide walks you through installing the Marwa Framework in your project.

If you want a complete application instead of integrating the core package into your own structure, use the recommended starter repository: [`memran/marwa-php`](https://github.com/memran/marwa-php).

## Recommended Starter Application

```bash
git clone https://github.com/memran/marwa-php.git
cd marwa-php
composer install
cp .env.example .env
```

Use the starter if you want:

- a working Marwa app structure immediately
- framework bootstrap files already wired
- routes, config, resources, and public entrypoint in place
- a concrete reference for how the framework is intended to be used

## Install the Framework Package Manually

## Requirements

| Requirement | Version | Notes |
|------------|---------|-------|
| PHP | 8.4+ | Required |
| Composer | 2.0+ | Package manager |
| Web Server | Any | PHP built-in, Nginx, etc. |
| Database | Optional | MySQL, PostgreSQL, or SQLite |

## Step 1: Install via Composer

Open your terminal and run:

```bash
composer require memran/marwa-framework
```

This installs the framework and all its dependencies.

## Step 2: Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` to configure your application:

```env
APP_NAME=MyApp
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database/app.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file
```

## Step 3: Verify Installation

Run the console command to verify:

```bash
php marwa
```

You should see a list of available commands:

```
Console Application

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display help
  -V, --version        Display version
      --env[=ENV]       The environment
  -q, --quiet          Do not output any message
      --ansi           Force ANSI output
      --no-ansi        Disable ANSI output
  -n, --no-interaction Do not ask any interactive question
      --profile        Display timing and memory usage information
  -v|vv|vvv, --verbose  Increase verbosity

Available commands:
  about                 Display the framework version
  db                   Database management commands
  make:command         Create a new command class
  make:controller      Create a new controller class
  make:seeder         Create a new seeder class
  ...
```

## Directory Permissions

Ensure the following directories are writable:

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## Web Server Setup

### Using PHP Built-in Server

```bash
php -S localhost:8000 -t public
```

Then visit `http://localhost:8000`

### Using Nginx

Configure your server block:

```nginx
server {
    listen 80;
    server_name myapp.test;
    root /path/to/myapp/public;

    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Using Apache

Ensure `.htaccess` in `public/` is enabled:

```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

## Database Setup

If using a database, create the database file:

```bash
# For SQLite
touch database/app.sqlite
chmod 775 database/app.sqlite

# Run migrations (if using marwa-db)
php marwa migrate
```

## Common Issues

### "Class not found" Errors

Run:

```bash
composer dump-autoload
```

### Permission Denied

Fix ownership:

```bash
# Linux
sudo chown -R www-data:www-data storage/ bootstrap/cache/
```

### Xdebug Performance

If Xdebug is installed, disable it for better performance:

```ini
; php.ini
xdebug.mode=off
```

## Next Steps

1. [Quick Start](quick-start.md) - Build your first application
2. [Project Structure](project-structure.md) - Understand the directory layout
3. [Configuration](../reference/config.md) - All config options
