# Models

`Marwa\Framework\Database\Model` is the framework-friendly base model built on top of `marwa-db` ORM. It keeps the upstream ORM behavior, then adds a small layer of convenience methods for common CRUD work such as table access, pagination, attribute normalization, create-or-update flows, and model state inspection.

Use it when you want an application model that feels concise in day-to-day development without hiding the underlying ORM.

## What The Framework Model Adds

The base class inherits the core ORM from `marwa-db`, then adds framework-level helpers such as:

- `newQuery()` as a clean entry point for query building
- `tableName()` to read the resolved table name
- `useConnection()` to switch the model connection
- `newInstance()` for creating model instances programmatically
- `paginate()` that returns hydrated model instances
- `firstWhere()` and `findBy()` for simple lookups
- `firstOrCreate()` and `updateOrCreate()` for common persistence flows
- `exists()` as an instance check
- `forceFill()` for bypassing fillable protection deliberately
- `syncOriginal()`, `isDirty()`, and `isClean()` for change tracking
- `fresh()` for reloading the current record
- `saveOrFail()` and `deleteOrFail()` for exception-based writes

That means you still get the ORM model behavior you expect, but the framework layer gives you a more ergonomic API for application code.

## Basic Example

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';

    protected static array $fillable = [
        'name',
        'email',
        'active',
        'meta',
    ];

    protected static array $casts = [
        'active' => 'bool',
        'meta' => 'json',
    ];
}
```

This is the standard pattern:

- set the table when it should not be inferred
- whitelist mass-assignable fields with `$fillable`
- define casts for attributes that need normalization

## Defining Table And Attributes

### Table Name

Use `$table` when you want to be explicit about the backing table:

```php
protected static ?string $table = 'users';
```

You can also read the resolved table name anywhere with:

```php
User::tableName();
```

### Fillable Fields

Use `$fillable` to control which attributes may be mass assigned:

```php
protected static array $fillable = [
    'name',
    'email',
    'active',
];
```

This matters for methods such as `create()`, `fill()`, and `update()` where arrays are applied to the model in bulk.

### Attribute Casting

The framework model normalizes input attributes using the model cast map before persistence helpers run.

Supported framework-level normalization in this wrapper includes:

- `json` and `array`
- `bool`
- `int`
- `float`

Example:

```php
protected static array $casts = [
    'active' => 'bool',
    'meta' => 'json',
    'login_count' => 'int',
    'score' => 'float',
];
```

Practical effect:

- `bool` values are cast to booleans
- `int` values are cast to integers
- `float` values are cast to floats
- `json` and `array` values are JSON-encoded when arrays or objects are provided

## Creating Records

Use `create()` when you want to insert and return a model in one step:

```php
$user = User::create([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'active' => true,
    'meta' => ['role' => 'admin'],
]);
```

You can also create an instance first and save it later:

```php
$user = User::newInstance([
    'name' => 'Bob',
    'email' => 'bob@example.com',
]);

$user->save();
```

If you want an exception when the write fails, use:

```php
$user->saveOrFail();
```

## Reading Records

For primary-key lookup:

```php
$user = User::find(1);
```

For a simple column lookup, use `findBy()` or `firstWhere()`:

```php
$user = User::findBy('email', 'alice@example.com');

$activeUser = User::firstWhere('active', true);
```

When you need more control, drop into the query builder:

```php
$users = User::newQuery()
    ->where('active', true)
    ->orderBy('name')
    ->get();
```

`newQuery()` is just a convenience alias for `query()`, so use whichever reads better in your codebase.

## First-Or-Create And Update-Or-Create

These are useful when your code is driven by unique business keys such as email, slug, or external IDs.

### `firstOrCreate()`

If a matching row exists, it is returned. Otherwise, a new row is created.

```php
$user = User::firstOrCreate(
    ['email' => 'alice@example.com'],
    ['name' => 'Alice', 'active' => true]
);
```

### `updateOrCreate()`

If a matching row exists, it is updated with the supplied values. Otherwise, a new row is created.

```php
$user = User::updateOrCreate(
    ['email' => 'alice@example.com'],
    ['name' => 'Alice Updated']
);
```

This pattern is especially useful for sync jobs, import pipelines, and idempotent writes.

## Updating Records

Once you have a model instance, update attributes and save:

```php
$user = User::findBy('email', 'alice@example.com');

$user->fill([
    'name' => 'Alice Smith',
]);

$user->save();
```

Or bypass fillable checks intentionally with `forceFill()`:

```php
$user->forceFill([
    'name' => 'Internal Override',
]);

$user->saveOrFail();
```

`forceFill()` is useful for internal framework code, trusted imports, or maintenance jobs where mass-assignment rules should not apply.

## Deleting Records

Delete a loaded model in the usual way:

```php
$user = User::find(1);
$user?->delete();
```

If a failure should raise an exception:

```php
$user->deleteOrFail();
```

## Pagination

The framework wrapper provides a `paginate()` helper that returns a structured array with hydrated model instances in `data`.

```php
$page = User::paginate(15, 1);
```

Returned structure:

```php
[
    'data' => [/* User instances */],
    'total' => 120,
    'per_page' => 15,
    'current_page' => 1,
    'last_page' => 8,
]
```

Example in a controller:

```php
$users = User::paginate(15, 1);

return $this->view('users/index', [
    'users' => $users['data'],
    'pagination' => $users,
]);
```

## Checking Model State

The framework model includes helpers for understanding whether an instance already exists and whether its attributes have changed.

### `exists()`

Checks whether the model currently has a primary key:

```php
if ($user->exists()) {
    // Existing persisted record
}
```

### `isDirty()` and `isClean()`

Use these to detect changes before saving:

```php
if ($user->isDirty('name')) {
    // The name has changed
}

if ($user->isClean()) {
    // Nothing has changed
}
```

### `syncOriginal()`

If you manually change attributes and want to treat the current state as the new baseline:

```php
$user->syncOriginal();
```

## Refreshing A Model

Use `fresh()` when you want a new instance reloaded from the database:

```php
$freshUser = $user->fresh();
```

This is useful after a write when database-level triggers, defaults, or other processes may have changed the stored row.

## Switching Connections

If your application uses more than one database connection, you can switch the model class to a named connection:

```php
User::useConnection('reporting');
```

Use this deliberately. Since it changes the model's static connection setting, it is best suited to well-bounded application flows.

## Practical Example

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';

    protected static array $fillable = [
        'name',
        'email',
        'active',
        'meta',
    ];

    protected static array $casts = [
        'active' => 'bool',
        'meta' => 'json',
    ];
}
```

Example usage:

```php
$user = User::updateOrCreate(
    ['email' => 'alice@example.com'],
    [
        'name' => 'Alice',
        'active' => true,
        'meta' => ['role' => 'admin'],
    ]
);

if ($user->isDirty()) {
    $user->saveOrFail();
}

$page = User::paginate(10, 1);
```

## Good Practices

- Keep models focused on persistence rules and lightweight domain behavior.
- Use `$fillable` consistently to avoid accidental mass assignment.
- Prefer `findBy()` and `firstWhere()` for straightforward lookups, and `newQuery()` when conditions become more complex.
- Use `updateOrCreate()` for idempotent writes instead of open-coded lookup-then-save sequences.
- Use `saveOrFail()` and `deleteOrFail()` in flows where silent failure would be a bug.
- Treat `forceFill()` and `useConnection()` as deliberate tools, not defaults.

## Related Reading

- [Model API](../api/models.md)
- [Database Tutorial](./database.md)
- [Seeding Tutorial](./seeding.md)
