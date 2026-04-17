# Step-by-Step Tutorial: Building Your First Application

This comprehensive tutorial walks you through building a complete web application with Marwa Framework.

## What We'll Build

A blog application with:
- Home page with posts list
- Individual post pages
- Contact form with validation
- JSON API endpoints
- Database integration

---

## Step 1: Create the Project Structure

Create this directory structure:

```bash
mkdir -p myblog/{app/{Controllers,Models,Http/Middleware,Providers},bootstrap/cache,config,database/{migrations,seeders},public,resources/views/{layouts,posts},routes,storage/{app/public,cache/framework,logs}}
```

Your structure should look like:

```
myblog/
├── app/
│   ├── Controllers/
│   ├── Http/Middleware/
│   ├── Models/
│   └── Providers/
├── bootstrap/cache/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   └── index.php
├── resources/
│   └── views/
│       ├── layouts/
│       └── posts/
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── app/public/
│   ├── cache/framework/
│   └── logs/
└── .env
```

---

## Step 2: Initialize Composer

Create `composer.json` in your project root:

```json
{
    "name": "yourname/myblog",
    "description": "A blog application built with Marwa Framework",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "memran/marwa-framework": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "serve": "php -S localhost:8000 -t public"
    },
    "minimum-stability": "stable"
}
```

Install dependencies:

```bash
composer install
```

---

## Step 3: Create the Environment File

Create `myblog/.env`:

```env
APP_NAME="My Blog"
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

LOG_ENABLE=true
LOG_CHANNEL=file

DEBUGBAR_ENABLED=true

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_DRIVER=file

DB_CONNECTION=sqlite
DB_DATABASE=database/app.sqlite

MAIL_DRIVER=log
```

---

## Step 4: Create the HTTP Entry Point

Create `myblog/public/index.php`:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Adapters\HttpRequestFactory;

define('START_TIME', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');
$app->boot();

$kernel = $app->make(HttpKernel::class);
$request = $app->make(HttpRequestFactory::class)->request();

$response = $kernel->handle($request);
$kernel->terminate($response);
```

---

## Step 5: Create the Apache/Nginx Config

**For Apache (`public/.htaccess`):**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

**For Nginx:**

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/myblog/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Step 6: Create Configuration Files

**Create `myblog/config/app.php`:**

```php
<?php

return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\SessionMiddleware::class,
        Marwa\Framework\Middlewares\SecurityMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class,
        Marwa\Framework\Middlewares\DebugbarMiddleware::class,
    ],
    'debugbar' => false,
    'collectors' => [],
];
```

**Create `myblog/config/database.php`:**

```php
<?php

return [
    'enabled' => true,
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('database/app.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ],
    'migrations' => 'migrations',
];
```

**Create `myblog/config/session.php`:**

```php
<?php

return [
    'enabled' => true,
    'autoStart' => false,
    'name' => 'myblog_session',
    'lifetime' => 120,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httpOnly' => true,
    'sameSite' => 'Lax',
    'encrypt' => false,
];
```

**Create `myblog/config/view.php`:**

```php
<?php

return [
    'viewsPath' => resources_path('views'),
    'cachePath' => storage_path('cache/views'),
    'debug' => false,
    'themePath' => resources_path('views/themes'),
    'activeTheme' => 'default',
];
```

---

## Step 7: Create the Service Provider

Create `myblog/app/Providers/AppServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;

class AppServiceProvider extends ServiceProviderAdapter
{
    public function register(): void
    {
        // Register your services here
    }

    public function boot(): void
    {
        // Boot your services here
    }
}
```

---

## Step 8: Create the Database

```bash
touch myblog/database/app.sqlite
```

Or create the table manually:

```sql
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    author TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO posts (title, content, author) VALUES 
    ('Welcome to My Blog', 'This is my first post!', 'Admin'),
    ('Second Post', 'This is the second post.', 'Admin');
```

---

## Step 9: Create the Controller

Create `myblog/app/Controllers/PostController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Marwa\Framework\HttpKernel;
use Marwa\Router\Response;
use App\Models\Post;

class PostController extends HttpKernel
{
    public function index(): Response
    {
        $posts = Post::all();
        
        return view('posts/index', [
            'posts' => $posts,
            'title' => 'My Blog'
        ]);
    }

    public function show(int $id): Response
    {
        $post = Post::find($id);
        
        if (!$post) {
            return Response::html('<h1>Post not found</h1>', 404);
        }
        
        return view('posts/show', [
            'post' => $post
        ]);
    }

    public function contact(): Response
    {
        return view('contact', [
            'title' => 'Contact Us'
        ]);
    }

    public function submitContact(): Response
    {
        try {
            $data = validate_request([
                'name' => 'required|string|min:2',
                'email' => 'required|email',
                'message' => 'required|string|min:10',
            ]);
            
            // Process the contact form
            // mailer()->to($data['email'])->send(...)
            
            return view('contact/success', [
                'message' => 'Thank you for contacting us!'
            ]);
            
        } catch (ValidationException $e) {
            return view('contact', [
                'errors' => $e->errors()->all(),
                'old' => $e->input(),
            ]);
        }
    }
}
```

---

## Step 10: Create the Model

Create `myblog/app/Models/Post.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Marwa\DB\ORM\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['title', 'content', 'author'];
}
```

---

## Step 11: Create Views

**Create `myblog/resources/views/layouts/main.twig`:**

```twig
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title|default('My Blog') }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        header { border-bottom: 2px solid #333; margin-bottom: 20px; }
        nav a { margin-right: 15px; }
        .post { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <header>
        <h1><a href="/">My Blog</a></h1>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>
    
    <main>
        {% block content %}{% endblock %}
    </main>
    
    <footer>
        <p>&copy; 2026 My Blog</p>
    </footer>
</body>
</html>
```

**Create `myblog/resources/views/posts/index.twig`:**

```twig
{% extends "layouts/main.twig" %}

{% block content %}
    <h2>All Posts</h2>
    
    {% for post in posts %}
        <article class="post">
            <h3><a href="/posts/{{ post.id }}">{{ post.title }}</a></h3>
            <p>By {{ post.author }} on {{ post.created_at }}</p>
            <p>{{ post.content|slice(0, 100) }}...</p>
        </article>
    {% else %}
        <p>No posts found.</p>
    {% endfor %}
{% endblock %}
```

**Create `myblog/resources/views/posts/show.twig`:**

```twig
{% extends "layouts/main.twig" %}

{% block content %}
    <article class="post">
        <h2>{{ post.title }}</h2>
        <p>By {{ post.author }} on {{ post.created_at }}</p>
        <div>{{ post.content }}</div>
    </article>
    
    <a href="/">← Back to all posts</a>
{% endblock %}
```

**Create `myblog/resources/views/contact.twig`:**

```twig
{% extends "layouts/main.twig" %}

{% block content %}
    <h2>Contact Us</h2>
    
    {% if errors %}
        <div class="error">
            <ul>
                {% for error in errors %}
                    <li>{{ error }}</li>
                {% endfor %}
            </ul>
        </div>
    {% endif %}
    
    <form method="POST" action="/contact/submit">
        <p>
            <label>Name:</label><br>
            <input type="text" name="name" value="{{ old('name')|default('') }}">
        </p>
        <p>
            <label>Email:</label><br>
            <input type="email" name="email" value="{{ old('email')|default('') }}">
        </p>
        <p>
            <label>Message:</label><br>
            <textarea name="message" rows="5">{{ old('message')|default('') }}</textarea>
        </p>
        <p>
            <button type="submit">Send</button>
        </p>
    </form>
{% endblock %}
```

---

## Step 12: Create Routes

**Create `myblog/routes/web.php`:**

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;
use App\Controllers\PostController;
use Marwa\Framework\Application;

Router::get('/', [PostController::class, 'index'])->name('home')->register();
Router::get('/posts/{id}', [PostController::class, 'show'])->name('post.show')->register();
Router::get('/contact', [PostController::class, 'contact'])->name('contact')->register();
Router::post('/contact/submit', [PostController::class, 'submitContact'])->name('contact.submit')->register();
Router::get('/about', fn() => Response::html('<h1>About Us</h1><p>We are a great blog!</p>'))->register();
Router::get('/health', fn() => Response::json(['status' => 'ok']))->register();
```

**Create `myblog/routes/api.php`:**

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;
use App\Models\Post;

Router::get('/api/posts', function() {
    return Response::json([
        'posts' => Post::all()
    ]);
})->register();

Router::get('/api/posts/{id}', function($id) {
    $post = Post::find($id);
    
    if (!$post) {
        return Response::json(['error' => 'Post not found'], 404);
    }
    
    return Response::json(['post' => $post]);
})->register();
```

---

## Step 13: Run the Application

```bash
cd myblog
php -S localhost:8000 -t public
```

Visit:
- `http://localhost:8000/` - Home page
- `http://localhost:8000/contact` - Contact form
- `http://localhost:8000/api/posts` - JSON API
- `http://localhost:8000/health` - Health check

---

## Complete Directory Structure

```
myblog/
├── app/
│   ├── Controllers/
│   │   └── PostController.php
│   ├── Http/Middleware/
│   ├── Models/
│   │   └── Post.php
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/cache/
├── config/
│   ├── app.php
│   ├── database.php
│   ├── session.php
│   └── view.php
├── database/
│   └── app.sqlite
├── public/
│   ├── index.php
│   └── .htaccess
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── main.twig
│       └── posts/
│           ├── index.twig
│           └── show.twig
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── app/public/
│   ├── cache/framework/
│   └── logs/
├── tests/
├── .env
├── composer.json
└── README.md
```

---

## Next Steps

You've built a complete application! Continue learning:

- [Controllers Tutorial](../tutorials/controllers.md) - More controller patterns
- [Validation Tutorial](../tutorials/validation.md) - Form validation
- [Database Tutorial](../tutorials/database.md) - Working with databases
- [Models Tutorial](../tutorials/models.md) - ORM features
- [View Tutorial](../tutorials/view.md) - Twig templates
