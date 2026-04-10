# Model API

Complete API reference for `Marwa\Framework\Database\Model`.

## Static Properties

```php
// Table name
protected static ?string $table = 'users';

// Mass-assignment
protected static array $fillable = ['name', 'email'];
protected static array $guarded = ['*'];

// Hide from array/JSON
protected static array $hidden = ['password'];

// Type casting
protected static array $casts = [
    'active' => 'bool',
    'options' => 'array',
    'rate' => 'float',
    'verified_at' => 'datetime',
];

// Date fields
protected static array $dates = ['deleted_at'];

// Eager loading
protected static array $with = ['profile'];
protected static array $withCount = ['posts'];
```

## Static Methods

### Query Methods

| Method | Description | Example |
|-------|-------------|---------|
| `newQuery()` | Get query builder | `User::newQuery()->where(...)` |
| `query()` | Get new query | `User::query()` |
| `tableName()` | Get table name | `User::tableName()` |
| `useConnection(string $name)` | Use connection | `User::useConnection('replica')` |

### Find Methods

| Method | Description | Example |
|-------|-------------|---------|
| `find(int $id)` | Find by ID | `User::find(1)` |
| `findOrFail(int $id)` | Find or throw | `User::findOrFail(1)` |
| `findBy(string $col, mixed $val)` | Find by column | `User::findBy('email', 'x@x.com')` |
| `firstWhere(string $col, mixed $val)` | First match | `User::firstWhere('email', 'x@x.com')` |
| `firstOrFail()` | First or throw | `User::firstOrFail()` |
| `firstOrCreate(array $data, array $values)` | First or create | `User::firstOrCreate(['email' => 'x@x.com'])` |
| `updateOrCreate(array $data, array $values)` | Update or create | `User::updateOrCreate(...)` |
| `all()` | Get all | `User::all()` |

### Pagination

| Method | Description | Example |
|-------|-------------|---------|
| `paginate(int $perPage, int $page)` | Paginate | `User::paginate(15)` |

## Instance Methods

### Save & Delete

| Method | Description | Example |
|-------|-------------|---------|
| `save()` | Save model | `$user->save()` |
| `saveOrFail()` | Save or throw | `$user->saveOrFail()` |
| `delete()` | Delete model | `$user->delete()` |
| `deleteOrFail()` | Delete or throw | `$user->deleteOrFail()` |
| `forceDelete()` | Force delete | `$user->forceDelete()` |
| `restore()` | Restore soft delete | `$user->restore()` |

### Refresh & Fresh

| Method | Description | Example |
|-------|-------------|---------|
| `fresh()` | Get fresh from DB | `$user->fresh()` |
| `refresh()` | Refresh attributes | `$user->refresh()` |

### Attributes

| Method | Description | Example |
|-------|-------------|---------|
| `isDirty(?string $key)` | Check changed | `$user->isDirty('name')` |
| `isClean(?string $key)` | Check unchanged | `$user->isClean()` |
| `forceFill(array $data)` | Force fill | `$user->forceFill([...])` |
| `getOriginal()` | Get original | `$user->getOriginal()` |
| `getAttributes()` | Get attributes | `$user->getAttributes()` |
| `exists` | Check exists | `$user->exists` |

## Relationships

### HasMany

```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}
```

### BelongsTo

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

### HasOne

```php
public function profile(): HasOne
{
    return $this->hasOne(Profile::class);
}
```

### BelongsToMany

```php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}
```

## Scopes

Define local scopes:

```php
public function scopeActive($query)
{
    return $query->where('active', true);
}

public function scopeVerified($query)
{
    return $query->whereNotNull('verified_at');
}
```

Usage: `User::active()->get()`

## Events

| Event | Trigger |
|-------|---------|
| `creating` | Before creating |
| `created` | After creating |
| `updating` | Before updating |
| `updated` | After updating |
| `saving` | Before save |
| `saved` | After save |
| `deleting` | Before delete |
| `deleted` | After delete |

## Cast Types

| Type | Description |
|------|-------------|
| `bool` | Convert to boolean |
| `int` / `integer` | Convert to integer |
| `float` | Convert to float |
| `string` | Convert to string |
| `array` | Encode/Decode JSON |
| `object` | Encode/Decode JSON |
| `datetime` | Carbon instance |
| `timestamp` | Unix timestamp |

## Example Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Marwa\Framework\Database\Model;
use Marwa\DB\ORM\Relations\HasMany;

final class User extends Model
{
    protected static ?string $table = 'users';
    
    protected static array $fillable = ['name', 'email', 'password'];
    
    protected static array $hidden = ['password'];
    
    protected static array $casts = [
        'active' => 'bool',
        'verified_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
```

## Full Usage Examples

### Create with Relationships

```php
$user = User::create(['name' => 'John', 'email' => 'john@test.com']);
$post = new Post(['title' => 'Hello', 'content' => 'World']);
$user->posts()->save($post);
```

### Query with Eager Loading

```php
$users = User::with(['posts'])
    ->withCount('posts')
    ->active()
    ->orderBy('name')
    ->paginate(15);
```

### Update with Conditions

```php
User::query()
    ->where('active', true)
    ->update(['active' => false]);
```

## Related

- [Models Tutorial](../tutorials/models.md) - Full tutorial
- [Seeding](seeding.md) - Database seeding
- [Database](database.md) - Database management