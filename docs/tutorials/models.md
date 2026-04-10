# Models

The framework model layer is a thin extension of `marwa-db` ORM. Extend `Marwa\Framework\Database\Model` to get a small CRUD-focused API without introducing a second ORM.

## Create a Model

```php
<?php

namespace App\Models;

use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';
    protected static array $fillable = ['name', 'email'];
    protected static array $casts = ['active' => 'bool'];
}
```

## CRUD

```php
$user = User::create(['name' => 'Alice', 'email' => 'alice@example.test']);
$found = User::findBy('email', 'alice@example.test');

User::updateOrCreate(
    ['email' => 'alice@example.test'],
    ['name' => 'Alice Updated']
);

$page = User::paginate(15, 1);
```

## Instance Helpers

```php
$user->forceFill(['name' => 'Alice Root'])->saveOrFail();
$fresh = $user->fresh();
$dirty = $user->isDirty('name');
```

## Generator

Use the scaffold command to generate a model class:

```bash
php marwa make:model Billing/Invoice --migration
```

The generated model extends `Marwa\Framework\Database\Model`, so the app gets the framework helper methods by default.
