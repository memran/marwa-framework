# Models

The framework model layer is built on `marwa-db` ORM. Extend `Marwa\Framework\Database\Model` for a clean CRUD-focused API.

## Quick Example

```php
<?php

namespace App\Models;

use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password'];
    protected static array $casts = ['active' => 'bool', 'verified_at' => 'datetime'];
}
```

## Model Properties

### Table Name

```php
protected static ?string $table = 'users';
```

### Fillable Fields

 Mass-assignment protection:

```php
protected static array $fillable = ['name', 'email', 'password'];
```

### Hidden Fields  

Exclude from array/JSON:

```php
protected static array $hidden = ['password'];
```

### Cast Types

```php
protected static array $casts = [
    'active' => 'bool',
    'verified_at' => 'datetime',
    'options' => 'array',
    'rate' => 'float',
];
```

### Date Fields

```php
protected static array $dates = ['deleted_at'];
```

### Eager Loading

```php
protected static array $with = ['profile'];
protected static array $withCount = ['posts'];
```

## CRUD Operations

### Create

```php
// Create new record
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Or create and save
$user = new User(['name' => 'John']);
$user->save();
```

### Read

```php
// Find by ID
$user = User::find(1);

// Find or fail
$user = User::findOrFail(1);

// Find by column
$user = User::findBy('email', 'john@example.com');

// First matching record
$user = User::firstWhere('email', 'john@example.com');

// All records
$users = User::all();

// Query builder
$users = User::query()
    ->where('active', true)
    ->orderBy('name')
    ->get();
```

### Update

```php
$user = User::find(1);
$user->name = 'Jane';
$user->save();

// Mass update
$user->update(['name' => 'Jane']);

// Update or create
User::updateOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Updated']
);
```

### Delete

```php
$user = User::find(1);
$user->delete();

// Force delete
$user->forceDelete();

// Delete or fail
$user->deleteOrFail();
```

### Pagination

```php
// Simple pagination
$users = User::paginate(15);

// With page number
$users = User::paginate(15, $page = 1);

// In controller
$users = User::paginate(15);
return view('users', ['users' => $users]);
```

## Relationships

### One to Many (hasMany)

```php
// User has many posts
final class User extends Model
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts; // Collection

// Or query
$posts = $user->posts()->where('active', true)->get();
```

### Belongs To

```php
// Post belongs to user
final class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// Usage
$post = Post::find(1);
$author = $post->user;
```

### Has One (hasOne)

```php
final class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
```

### Many to Many (belongsToMany)

```php
final class User extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

## Scopes (Local Scopes)

```php
final class User extends Model
{
    // Local scope
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}

// Usage
$users = User::active()->get();
$users = User::verified()->search('john')->get();
```

## Accessors & Mutators

### Accessor

```php
final class User extends Model
{
    // Get full name accessor
    public function getFullNameAttribute(): string
    {
        return "{$this->name}";
    }
}

// Usage
$user = User::find(1);
echo $user->full_name; // "John Doe"
```

### Mutator

```php
final class User extends Model
{
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value);
    }
}

// Usage
$user->password = 'secret'; // Automatically hashed
```

## Query Builder

```php
$users = User::newQuery()
    ->select('id', 'name', 'email')
    ->where('active', true)
    ->orderBy('name')
    ->limit(10)
    ->get();
```

## Eager Loading

### With Relationships

```php
// Load user with posts
$users = User::with('posts')->get();

// Multiple relationships
$users = User::with(['posts', 'profile'])->get();
```

### With Count

```php
$users = User::withCount('posts')->get();

// Access count
foreach ($users as $user) {
    echo $user->posts_count;
}
```

### Lazy Eager Loading

```php
$user = User::find(1);
$user->load('posts');
```

## Model Events

Hooks during model lifecycle:

```php
User::creating(function (User $user) {
    // Before creating
});

User::created(function (User $user) {
    // After creating
});

User::updating(function (User $user) {
    // Before updating  
});

User::updated(function (User $user) {
    // After updating
});

User::saving(function (User $user) {
    // Before saving
});

User::saved(function (User $user) {
    // After saving
});

User::deleting(function (User $user) {
    // Before deleting
});

User::deleted(function (User $user) {
    // After deleting
});
```

## Instance Helpers

```php
// Check if dirty (changed)
$user->isDirty('name'); // true/false

// Check if clean (unchanged)
$user->isClean();

// Get fresh instance from DB
$fresh = $user->fresh();

// Refresh current instance
$user->refresh();

// Force fill (bypass fillable)
$user->forceFill(['password' => 'hashed']);

// Check exists
$user->exists;

// Save or fail (throws on error)
$user->saveOrFail();

// Delete or fail
$user->deleteOrFail();

// Get original values
$user->getOriginal();

// Get attributes
$user->getAttributes();
```

## Generator Command

Create a model with migration:

```bash
php marwa make:model User
php marwa make:model Blog/Post
php marwa make:model User --migration
php marwa make:model User --seeder
```

## Complete Example

```php
<?php

namespace App\Models;

use Marwa\Framework\Database\Model;
use Marwa\DB\ORM\Relations\HasMany;
use Marwa\DB\ORM\Relations\BelongsTo;

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

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

// Usage
$users = User::active()
    ->with('posts')
    ->withCount('posts')
    ->orderBy('name')
    ->paginate(15);
```

## Related

- [Seeding](seeding.md) - Database seeding
- [Database](database.md) - Database management
- [Validation](validation.md) - Input validation