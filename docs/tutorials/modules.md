# Modules Guide

This guide shows how modules work in the Marwa Framework as it is implemented in this repository.

Modules are discovered from configured directories, registered through `marwa-module`, and then integrated into the framework bootstrap so their providers, routes, views, commands, migrations, and seeders can participate in the application runtime.

## What a Module Provides

A module can contribute:

- a `manifest.php` or `manifest.json`
- one or more module service providers
- main navigation menu items
- HTTP and API route files
- Twig views
- console commands
- database migrations
- database seeders

The framework reads those pieces from module manifests and configured conventions. It does not require manual module registration in `config/app.php`.

## Quick Start

Generate a new module:

```bash
php marwa make:module Blog
```

The generated structure matches the framework stubs:

```text
modules/
└── Blog/
    ├── BlogServiceProvider.php
    ├── Console/
    │   └── Commands/
    ├── database/
    │   └── migrations/
    ├── manifest.php
    ├── resources/
    │   └── views/
    │       └── index.twig
    └── routes/
        └── http.php
```

Map your application namespace to `modules/` in the host app `composer.json` if you use the default generated namespace:

```json
{
  "autoload": {
    "psr-4": {
      "App\\Modules\\": "modules/"
    }
  }
}
```

Then refresh autoloading:

```bash
composer dump-autoload
```

## Enable Modules

Modules are disabled by default. Enable them in `config/module.php`:

```php
<?php

return [
    'enabled' => true,
    'paths' => [
        base_path('modules'),
    ],
    'cache' => bootstrap_path('cache/modules.php'),
    'forceRefresh' => false,
    'commandPaths' => [
        'commands',
    ],
    'commandConventions' => [
        'Console/Commands',
        'src/Console/Commands',
    ],
    'migrationsPath' => [
        'database/migrations',
    ],
    'seedersPath' => [
        'database/seeders',
    ],
];
```

Important keys:

- `enabled`: turns module integration on or off
- `paths`: module root directories to scan
- `cache`: manifest cache file path used by `module:cache`
- `forceRefresh`: ignore cached module manifests and rescan
- `commandPaths`: manifest `paths` keys treated as command directories
- `commandConventions`: module-relative fallback command directories
- `migrationsPath`: manifest `paths` keys checked for migrations if the manifest does not list migration files directly
- `seedersPath`: manifest `paths` keys treated as seeder directories

## Module Manifest

Each module needs exactly one manifest file: `manifest.php` or `manifest.json`.

Typical `manifest.php`:

```php
<?php

declare(strict_types=1);

return [
    'name' => 'Blog Module',
    'slug' => 'blog',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Blog\BlogServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
        'migrations' => 'database/migrations',
        'seeders' => 'database/seeders',
    ],
    'routes' => [
        'http' => 'routes/http.php',
        'api' => 'routes/api.php',
    ],
    'migrations' => [
        'database/migrations/2026_01_01_000000_create_posts_table.php',
    ],
];
```

Standard manifest fields that the runtime exposes are:

- `name`
- `slug`
- `version`
- `providers`
- `paths`
- `routes`
- `migrations`

The framework also reads `requires` and `dependencies` from the raw manifest for dependency validation during bootstrap.

## Module Service Provider

Generated module providers implement `Marwa\Module\Contracts\ModuleServiceProviderInterface`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog;

use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class BlogServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->set('module.blog.registered', true);
    }

    public function boot($app): void
    {
        $app->set('module.blog.booted', true);
    }
}
```

Use `register()` for bindings and service setup. Use `boot()` for runtime behavior that depends on registered services.

The framework boots module providers automatically after discovery. You do not need to add them to `config/app.php`.

## Menus

Modules can contribute to the shared main navigation through `Marwa\Framework\Navigation\MenuRegistry`.

Typical module usage inside `boot($app)`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog;

use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class BlogServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        /** @var MenuRegistry $menu */
        $menu = $app->make(MenuRegistry::class);

        $menu->add([
            'name' => 'blog',
            'label' => 'Blog',
            'url' => '/blog',
            'order' => 20,
        ]);

        $menu->add([
            'name' => 'blog.posts',
            'label' => 'Posts',
            'url' => '/blog/posts',
            'parent' => 'blog',
            'order' => 10,
        ]);
    }
}
```

Supported menu item fields:

- `name`: required stable identifier
- `label`: required text shown in the menu
- `url`: required target URL
- `parent`: optional parent item name for nesting
- `order`: optional integer sort order
- `icon`: optional icon token or class name
- `visible`: optional boolean or callable visibility rule

Behavior:

- duplicate `name` values throw `Marwa\Framework\Exceptions\MenuConfigurationException`
- child items are nested under `parent`
- items are sorted by `order`, then `label`
- items whose parent does not exist are skipped from the built menu tree

The framework shares the final menu tree to views as `mainMenu`, and you can also resolve it manually with `menu()->tree()`.

## Routes

The module bootstrapper automatically loads route files declared in the manifest under `routes.http` and `routes.api`.

Example `routes/http.php`:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/blog', fn () => Response::json([
    'module' => 'Blog Module',
    'ok' => true,
]))->register();
```

Module routes are loaded only when:

- modules are enabled
- the app is not running in console mode
- no compiled route cache file already exists

## Views

If a module manifest defines `paths.views`, the framework registers that directory as a Twig namespace using the module slug.

For a module with slug `blog`, render templates with the `@blog/...` convention:

```php
return view('@blog/index.twig', [
    'title' => 'Blog',
]);
```

With this manifest entry:

```php
'paths' => [
    'views' => 'resources/views',
],
```

the template path resolves to:

```text
modules/Blog/resources/views/index.twig
```

## Commands

Module command discovery runs through:

- manifest-defined directories referenced by keys listed in `commandPaths`
- default conventions such as `Console/Commands` and `src/Console/Commands`

Generated modules already include `Console/Commands`, and the generator adds `paths.commands` to the manifest.

Example command:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'blog:hello', description: 'Example module command')]
final class BlogHelloCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello from the Blog module.');

        return self::SUCCESS;
    }
}
```

## Migrations and Seeders

Run module migrations:

```bash
php marwa module:migrate
```

Run module seeders:

```bash
php marwa module:seed
```

Migration discovery works in two ways:

- explicit file list in manifest `migrations`
- manifest `paths` entries matched by `migrationsPath` config, typically `database/migrations`

Seeder discovery uses `seedersPath` config against manifest `paths` entries, typically `database/seeders`.

Example migration file:

```php
<?php

use Marwa\DB\CLI\AbstractMigration;
use Marwa\DB\Schema\Schema;

return new class extends AbstractMigration {
    public function up(): void
    {
        Schema::create('blog_posts', function ($table): void {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('blog_posts');
    }
};
```

## Module Dependencies

Modules can declare other required modules in the manifest using `requires` or `dependencies`:

```php
return [
    'name' => 'Auth Module',
    'slug' => 'auth',
    'providers' => [
        App\Modules\Auth\AuthServiceProvider::class,
    ],
    'requires' => [
        'user',
    ],
];
```

During bootstrap, the framework validates those dependencies before module providers are booted.

Behavior:

- dependency names are matched case-insensitively
- missing dependencies fail fast
- the framework throws `Marwa\Framework\Exceptions\ModuleDependencyException`

Example failure:

```text
Module [auth] requires missing module(s): user.
```

Use lowercase slugs in examples and manifests even though the dependency check is case-insensitive.

## Reading Module Information at Runtime

Use the helper APIs to inspect loaded modules:

```php
if (has_module('blog')) {
    $blog = module('blog');

    $name = $blog->name();
    $slug = $blog->slug();
    $manifest = $blog->manifest();
}
```

You can also access the application-level module registry:

```php
$modules = app()->modules();
$hasBlog = app()->hasModule('blog');
$blog = app()->module('blog');
```

Important detail about metadata:

- `module('blog')->manifest()` returns the normalized manifest that `marwa-module` keeps at runtime
- standard manifest fields are available
- arbitrary custom manifest keys are not exposed through `manifest()` today

That means this works:

```php
$manifest = module('blog')->manifest();
$version = $manifest['version'] ?? null;
```

But custom keys should not be relied on through that API unless the package is extended to preserve them.

You can also access the shared menu registry at runtime:

```php
$menuTree = menu()->tree();
$flatMenu = menu()->all();
```

## Caching

Build the module manifest cache:

```bash
php marwa module:cache
```

Clear the module manifest cache:

```bash
php marwa module:clear
```

The cache file path is controlled by `config/module.php` and is also used by bootstrap cache commands.

## Troubleshooting

If a module is not loading:

1. Confirm `config/module.php` has `'enabled' => true`.
2. Confirm the module directory is inside one of `module.paths`.
3. Confirm the module has exactly one valid manifest file.
4. Confirm provider classes exist and implement `ModuleServiceProviderInterface`.
5. Confirm the host app autoload maps `App\\Modules\\` to `modules/`.
6. Clear and rebuild the module cache with `module:clear` and `module:cache`.

If `module('slug')` fails, the module was not discovered or bootstrapped.

If dependency validation fails, add the missing module or remove the declared dependency.

## Console Commands

| Command | Description |
|---------|-------------|
| `make:module` | Generate a module scaffold |
| `module:cache` | Build the module manifest cache |
| `module:clear` | Remove the module manifest cache |
| `module:migrate` | Run discovered module migrations |
| `module:seed` | Run discovered module seeders |

## Related

- [API: Application](../api/application.md)
- [API: Helpers](../api/helpers.md)
- [API: Configuration](../api/configuration.md)
- [Tutorial: Console](./console.md)
