# Modules Guide

This guide covers creating and using modules in the Marwa Framework.

## Overview

Modules are self-contained packages that extend the framework:

- Routes
- Controllers
- Models
- Views
- Config
- Service providers

## Quick Start

### Create Module

```bash
php marwa make:module Blog
```

### Module Structure

```
modules/
└── Blog/
    ├── manifest.php
    ├── routes/
    │   └── web.php
    ├── resources/
    │   └── views/
    │       └── index.twig
    ├── migrations/
    ├── Providers/
    │   └── BlogServiceProvider.php
    └── app/
        Controllers/
        └── Models/
```

## manifest.php

Each module requires a manifest file:

```php
// modules/Blog/manifest.php
return [
    'name' => 'Blog',
    'slug' => 'blog',
    'version' => '1.0.0',
    'description' => 'Blog module for Marwa',
    
    'providers' => [
        App\Modules\Blog\Providers\BlogServiceProvider::class,
    ],
    
    'routes' => [
        'http' => 'routes/http.php',
    ],
    
    'paths' => [
        'views' => 'resources/views',
    ],
    
    'migrations' => [
        'database/migrations/2026_01_01_000000_create_posts_table.php',
    ],
];
```

## Configuration

### config/module.php

```php
return [
    'enabled' => true,
    
    'paths' => [
        'modules' => base_path('modules'),
    ],
    
    'scan' => [
        'enabled' => true,
        'paths' => [
            base_path('modules/*'),
            base_path('vendor/*/marwa-module'),
        ],
    ],
    
    'cache' => [
        'enabled' => true,
        'path' => bootstrap_path('cache/modules.php'),
    ],
];
```

## Registering Modules

### Auto-Discovery

Modules are automatically discovered from configured paths.

### Manual Registration

In `config/app.php`:

```php
return [
    'providers' => [
        App\Modules\Blog\Providers\BlogServiceProvider::class,
    ],
];
```

## Module Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Providers;

use Marwa\DB\Schema\Schema;
use Marwa\Framework\Contracts\ServiceProviderInterface;
use League\Container\Container;

final class BlogServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register module services
        $container->addShared(PostService::class, fn() => new PostService());
    }

    public function boot(): void
    {
        // Register routes
        require_once __DIR__ . '/../routes/web.php';
        
        // Register views
        view()->addNamespace('blog', __DIR__ . '/../resources/views');
        
        // Run migrations
        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }
}
```

## Module Routes

### Define Routes

```php
// modules/Blog/routes/web.php

use App\Modules\Blog\Controllers\PostController;
use Marwa\Framework\Facades\Router;

Router::get('/blog', [PostController::class, 'index']);
Router::get('/blog/{slug}', [PostController::class, 'show']);
Router::post('/blog', [PostController::class, 'store']);
```

### Route Prefixing

```php
Router::prefix('blog')->group(function () {
    Router::get('/', [PostController::class, 'index']);
    Router::get('/{slug}', [PostController::class, 'show']);
});
// Results in /blog, /blog/{slug}
```

## Module Views

### Create View

In your module's `resources/views/` directory:
- Use `.twig` file extension
- Standard Twig syntax applies (extends, block, for, etc.)
- See [Twig Documentation](https://twig.symfony.com/doc/) for full syntax

Example file: `modules/Blog/resources/views/index.twig`

### Use View

```php
// In controller
return view('blog::index.twig', ['posts' => $posts]);

// Or with view namespace
return view('index', ['posts' => $posts]);
```

### Extend Layout

```html
{# modules/Blog/resources/views/layout.twig #}
<!DOCTYPE html>
<html>
<head>
    <!-- block tags would go here in actual templates -->
    <title>Blog Layout</title>
</head>
<body>
    <!-- content would go here -->
</body>
</html>
```

## Module Assets

### Publish Assets

```bash
php marwa vendor:publish --module=Blog
```

### Use Assets

```php
// In views
<link rel="stylesheet" href="{{ asset('blog::css/style.css') }}">
<script src="{{ asset('blog::js/script.js') }}"></script>
```

## Module Commands

### Register Commands

```php
// In service provider
$container->addShared(CommandRegistry::class)
    ->addArgument($app)
    ->addArgument($container)
    ->addArgument($logger);

// Add module commands
$registry->registerMany([
    BlogPostsList::class,
    BlogPostsPublish::class,
]);
```

### Module Command

```php
#[AsCommand(name: 'blog:posts:list', description: 'List blog posts')]
final class BlogPostsList extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addArgument('status', InputArgument::OPTIONAL, 'Post status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $posts = Post::query();
        
        if ($status = $input->getArgument('status')) {
            $posts->where('status', $status);
        }
        
        foreach ($posts->get() as $post) {
            $output->writeln("{$post->id}: {$post->title}");
        }
        
        return Command::SUCCESS;
    }
}
```

## Database in Modules

### Run Migrations

```bash
php marwa module:migrate
```

`module:migrate` scans discovered modules and runs their migrations through `marwa-db`'s
`MigrationRepository`. When a manifest declares individual migration files, the framework
normalizes them to their containing directories so anonymous-class migration files are executed
and recorded in the `migrations` table the same way as application migrations.

### Create Migrations

```php
// modules/Blog/migrations/2024_01_01_create_posts_table.php
use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('blog_posts', function ($table): void {
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->string('featured_image');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('blog_posts');
    }
};
```

## Module Configuration

### Module Config File

```php
// modules/Blog/config/blog.php
return [
    'posts_per_page' => 10,
    
    'allow_comments' => true,
    
    'categories' => [
        'General',
        'Tech',
        'Lifestyle',
    ],
    
    'features' => [
        'markdown' => true,
        'social_share' => true,
    ],
];
```

### Access Config

```php
// In module
config('blog.posts_per_page');

// From outside
config('blog::posts_per_page');
```

## Events in Modules

### Dispatch Events

```php
$app->dispatch(new PostPublished($post));
```

### Listen to Events

```php
// In service provider
$eventListener = $container->get(EventDispatcherInterface::class);
$eventListener->listen(PostPublished::class, function ($event) {
    logger()->info('Post published', ['id' => $event->post->id]);
});
```

## Module Dependencies

### Declare Dependencies

```php
// in manifest.php
return [
    'name' => 'Blog',
    'requires' => [
        'comment' => '^1.0',
    ],
];
```

### Install Dependencies

```bash
composer require vendor/comment-module
```

## Disabling Modules

### Disable Single Module

```php
// config/app.php
return [
    'modules' => [
        'disabled' => [
            'Blog' => 'Comment',
        ],
    ],
];
```

### Conditional Loading

```php
// In manifest.php
return [
    'enabled' => env('BLOG_ENABLED', true),
];
```

## Module Publishing

### Publish Config

```bash
php marwa vendor:publish --module=Blog --tag=config
```

### Publish Views

```bash
php marwa vendor:publish --module=Blog --tag=views
```

### Publish Assets

```bash
php marwa vendor:publish --module=Blog --tag=public
```

## Console Commands

| Command | Description |
|---------|-------------|
| `make:module` | Create new module |
| `module:cache` | Cache modules |
| `module:clear` | Clear module cache |
| `module:migrate` | Run discovered module migrations |

## Best Practices

### 1. Use Namespaces

```php
namespace App\Modules\Blog\Controllers;
```

### 2. Follow Conventions

```
Module/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Services/
├── config/
├── migrations/
├── Providers/
├── resources/
│   ├── assets/
│   └── views/
└── routes/
```

### 3. Version Your Module

```php
return [
    'version' => '1.0.0',
    'marwa_version' => '^1.0',
];
```

## Related

- [Console Commands](console/index.md) - CLI reference
- [Routing](controllers.md) - Route definitions
- [Deployment](deployment.md) - Production setup
