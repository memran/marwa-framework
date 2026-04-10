# Troubleshooting

This guide covers common issues and how to fix them.

## General Issues

### "Class Not Found" Error

**Symptom:**
```
Error: Class 'App\Controllers\UserController' not found
```

**Solution:**
```bash
composer dump-autoload
```

### Blank White Page

**Symptom:**
White screen with no error message.

**Solutions:**

1. Enable error display in `.env`:
```env
APP_DEBUG=true
```

2. Check PHP error logs:
```bash
tail -f storage/logs/app.log
```

3. Check PHP error log location:
```php
php -i | grep error_log
```

### 500 Internal Server Error

**Symptom:**
HTTP 500 error response.

**Solutions:**

1. Check the error log:
```bash
tail -f storage/logs/app.log
```

2. Enable debug mode:
```env
APP_DEBUG=true
```

3. Check permissions:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## Database Issues

### "Database Not Found"

**Symptom:**
```
SQLSTATE[HY000]: General error: 14 unable to open database file
```

**Solution (SQLite):**
```bash
touch database/app.sqlite
chmod 775 database/app.sqlite
```

### "Access Denied for User"

**Symptom:**
```
SQLSTATE[28000]: Access denied for user 'root'@'localhost'
```

**Solution:** Check `.env`:
```env
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Migration Failed

**Symptom:**
Migration errors.

**Solutions:**

1. Check database connection in `.env`
2. Run with verbose output:
```bash
php marwa migrate -vvv
```
3. Check migrations path in `config/database.php`

### "No Such Table"

**Symptom:**
Table doesn't exist errors.

**Solution:** Run migrations:
```bash
php marwa migrate
```

## Session Issues

### "Session Not Starting"

**Symptom:**
Sessions don't persist.

**Solution:**

1. Check session config in `config/session.php`
2. Ensure storage is writable:
```bash
chmod -R 775 storage/framework/sessions/
```

### "Session Cookie Not Set"

**Symptom:**
Login doesn't persist.

**Solution:** Check cookie settings:
```php
// config/session.php
return [
    'driver' => 'cookie', // or 'file'
    'cookie_name' => 'marwa_session',
    'cookie_lifetime' => 120,
];
```

## Email Issues

### "SMTP Connection Failed"

**Symptom:**
Email not sending.

**Solution:** Check mail config in `config/mail.php`:
```php
return [
    'driver' => 'smtp',
    'host' => 'smtp.mailtrap.io',
    'port' => 2525,
    'username' => 'your_username',
    'password' => 'your_password',
];
```

### "From Address Invalid"

**Symptom:**
Email validation error.

**Solution:** Set valid from address:
```php
return [
    'from' => [
        'address' => 'noreply@yourdomain.com',
        'name' => 'Your App',
    ],
];
```

## Cache Issues

### "Cache Not Working"

**Symptom:**
Cached data doesn't persist.

**Solutions:**

1. Clear cache:
```bash
php marwa cache:clear
```

2. Check cache driver in `.env`:
```env
CACHE_DRIVER=file
```

### "Route Cache Error"

**Symptom:**
Routes not loading after caching.

**Solution:** Clear route cache:
```bash
php marwa route:clear
```

## Console Issues

### "Command Not Found"

**Symptom:**
```
Command "make:user" is not defined.
```

**Solution:**
```bash
php marwa list
```
to see available commands.

### "Permission Denied"

**Symptom:**
Cannot execute `marwa`.

**Solution:**
```bash
chmod +x marwa
```

## Permission Issues

### "Permission Denied" on Storage

**Solution:**
```bash
# Linux/Apache
sudo chown -R www-data:www-data storage/ bootstrap/cache/

# macOS
sudo chown -R _www:_www storage/ bootstrap/cache/
```

### "Directory Not Writable"

**Solution:**
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod -R 775 database/
```

## Performance Issues

### Slow Response Times

**Solutions:**

1. Enable route caching:
```bash
php marwa route:cache
```

2. Use OPcache:
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
```

3. Disable Xdebug in production:
```ini
; php.ini
xdebug.mode=off
```

### High Memory Usage

**Solutions:**

1. Check for N+1 queries in models
2. Use eager loading:
```php
$users = User::with('posts')->get();
```

3. Optimize database queries with indexes

## Getting Help

### Enable Debug Mode

```env
APP_DEBUG=true
APP_ENV=local
```

### Check Logs

```bash
# Application logs
tail -f storage/logs/app.log

# Framework logs
tail -f storage/logs/framework.log
```

### Run Tests

```bash
composer test
```

### Run Static Analysis

```bash
composer stan
```

## Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| 404 | Not found | Check route exists |
| 500 | Server error | Check logs |
| 419 | CSRF token | Refresh page |
| 422 | Validation | Check input |
| 401 | Unauthorized | Check auth |

## Next Steps

- [Deployment](deployment.md) - Production setup
- [Testing](testing.md) - Write tests
- [Configuration](../reference/config.md) - Config options