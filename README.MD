# Marwa Framework

[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![License](https://img.shields.io/packagist/l/memran/marwa-framework.svg?style=flat-square)](https://packagist.org/packages/memran/marwa-framework)
[![PHP Version](https://img.shields.io/packagist/php-v/memran/marwa-framework.svg?style=flat-square)](https://php.net)

A lightweight, modular PHP micro-framework built for speed, scalability, and simplicity. It adheres to PSR standards and offers rich features like routing, ORM, CLI tools, authentication, mailing, and more — making it ideal for rapid API or web application development.

---

## 🚀 Features

- PSR-4 Autoloading & PSR-7 HTTP Message support
- Expressive Routing System
- MVC Pattern
- ORM with Relationships
- Authentication & Authorization
- Middleware Support
- Queue Job Handling
- Notification System (Email/SMS)
- Session & Cookie Management
- Templating with Smarty
- Built-in CLI (`marwa`)

---

## 📦 Requirements

- PHP >= 8.2
- Composer
- MySQL or supported database
- Optional: Redis (for cache/session/queue)

---

## 📥 Installation

```bash
git clone https://github.com/memran/marwa-framework.git
cd marwa-framework
composer install
```

Create `.env` file:

```bash
cp .env.example .env
```

Update your environment variables for DB, mail, etc.

---

## 🔧 Running the App

Use PHP's built-in web server:

```bash
php -S localhost:8000 -t public
```

---

## 🧭 Example Route

```php
// routes/web.php

use Marwa\Framework\Facades\Route;

Route::get('/', function () {
    return 'Hello from Marwa!';
})->register();
```

---

## 🎮 Using the CLI

The `marwa` CLI allows code generation and system operations:

```bash
php marwa make:controller HomeController
php marwa make:model Product
php marwa migrate
```

---

## 📜 License

Licensed under the [MIT License](LICENSE)

---

## 🤝 Contributing

We welcome contributions. Feel free to fork, make a pull request, or suggest features via GitHub issues.

---

## 👤 Author

**Mohammad Emran**  
[GitHub](https://github.com/memran) | [LinkedIn](https://linkedin.com/in/memran)
