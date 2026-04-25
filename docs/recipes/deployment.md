# Deployment Guide

This guide covers deploying your Marwa Framework application to production.

## Prerequisites

- A server with PHP 8.4+
- Composer
- Web server (Nginx/Apache)
- Database (if needed)

## Pre-Deployment Checklist

- [ ] All tests passing (`composer test`)
- [ ] No static analysis errors (`composer stan`)
- [ ] Code style passes (`php bin/ci`)
- [ ] Environment configured
- [ ] Database created

## Step 1: Prepare Your Application

### 1.1 Update Environment

Create production `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=sqlite
# or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=yourapp
# DB_USERNAME=youruser
# DB_PASSWORD=securepassword

CACHE_DRIVER=file
SESSION_DRIVER=file
LOG_CHANNEL=daily
```

### 1.2 Generate Application Key

```bash
php marwa key:generate
```

### 1.3 Cache Configuration

```bash
php marwa config:cache
php marwa route:cache
php marwa bootstrap:cache
```

## Step 2: Deploy to Server

### Option A: Simple Deploy (SCP/FTP)

1. Upload all files except `vendor/` to server
2. Run `composer install --no-dev --optimize-autoloader` on server
3. Set permissions

### Option B: Git Deploy

```bash
# On server
git clone your-repo /var/www/yourapp
cd /var/www/yourapp
composer install --no-dev --optimize-autoloader
```

### Option C: Deploy Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Deploying..."

# Pull latest
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Cache config
php marwa config:cache
php marwa route:cache
php marwa bootstrap:cache

# Clear cache
php marwa cache:clear
php marwa route:clear

echo "Deployed!"
```

## Step 3: Web Server Configuration

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/yourapp/public;
    index index.php;

    # Laravel-style rewrite
    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Deny access
    location / {
        deny all;
    }
    location /storage {
        deny all;
    }
}
```

### Apache

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/yourapp/public

    <Directory /var/www/yourapp/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Deny access to sensitive directories
    <Directory /var/www/yourapp/storage>
        Require all denied
    </Directory>
</VirtualHost>
```

## Step 4: Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/yourapp

# Set permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod -R 775 database/

# Make marwa executable
chmod +x marwa
```

## Step 5: Queue Workers (Optional)

If using queues:

```bash
# Systemd service
# /etc/systemd/system/yourapp-worker.service
[Unit]
Description=YourApp Queue Worker

[Service]
ExecStart=/usr/bin/php /var/www/yourapp marwa queue:work
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
```

```bash
# Enable service
sudo systemctl daemon-reload
sudo systemctl enable yourapp-worker
sudo systemctl start yourapp-worker
```

## Step 6: Scheduler (Optional)

Add cron job:

```bash
# crontab -e
* * * * * /usr/bin/php /var/www/yourapp marwa schedule:run --for=60 --sleep=1 >> /dev/null 2>&1
```

## Step 7: SSL/HTTPS

### Using Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

## Step 8: Monitoring

### Health Check

Create `/storage/framework/health`:
```
OK
```

### Log Monitoring

```bash
# Watch logs
tail -f storage/logs/app.log

# Log rotation
# /etc/logrotate.d/yourapp
/var/www/yourapp/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
}
```

## Step 9: Performance Optimization

### 1. Enable OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Disable Xdebug

```ini
; php.ini
xdebug.mode=off
xdebug.start_with_request = no
```

### 3. Database Optimization

```bash
# Optimize database
php marwa db:optimize

# Analyze database
php marwa db:analyze
```

## Step 10: Backup

### Database Backup

```bash
php marwa db:backup --path=/backup/database.sql
```

### Automated Backups

```bash
# crontab -e
# Daily at 2am
0 2 * * * /var/www/yourapp marwa db:backup --path=/backup/$(date +\%Y\%m\%d).sql
```

## Troubleshooting

### White Screen

1. Check logs: `tail storage/logs/app.log`
2. Enable debug: `APP_DEBUG=true`

### 500 Error

1. Check permissions: `chmod -R 775 storage/`
2. Check PHP error log
3. Clear caches: `php marwa cache:clear`

### Slow Performance

1. Enable OPcache
2. Use route caching
3. Check database queries

## Quick Reference

| Task | Command |
|------|----------|
| Cache config | `php marwa config:cache` |
| Clear cache | `php marwa cache:clear` |
| Optimize DB | `php marwa db:optimize` |
| Backup DB | `php marwa db:backup` |
| Run scheduler | `php marwa schedule:run` |

## Related

- [Troubleshooting](troubleshooting.md) - Common issues
- [Testing](testing.md) - Write tests
- [Configuration](../reference/config.md) - Config options
