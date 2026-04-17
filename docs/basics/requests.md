# Requests

Requests represent incoming HTTP requests in your application. Marwa Framework uses PSR-7 standard for request handling.

## Accessing the Request

### Using the Helper

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

// In a route handler
Router::get('/users/{id}', function($id) {
    $request = request();

    // Get user ID from route parameter
    $userId = $id;

    return Response::json(['user_id' => $userId]);
})->register();
```

### Via Dependency Injection

```php
<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    public function show(ServerRequestInterface $request, int $id)
    {
        return Response::json(['user_id' => $id]);
    }
}
```

## Getting Input

### Get Single Value

```php
// From query string: /search?q=keyword
$query = request('q'); // "keyword"

// From POST data
$name = request('name');

// With default value
$theme = request('theme', 'light');
```

### Get All Input

```php
// All input as array
$all = request()->all();

// Only specific keys
$specific = request()->only(['name', 'email']);
```

### Check if Input Exists

```php
if (request()->has('name')) {
    // Input exists
}

if (request()->filled('email')) {
    // Input exists and is not empty
}
```

## Route Parameters

```php
// Route: /users/{id}/posts/{post}
Router::get('/users/{id}/posts/{post}', function($id, $post) {
    $request = request();

    // Get from route
    $userId = $request->getAttribute('id');
    $postId = $request->getAttribute('post');

    return Response::json([
        'user_id' => $userId,
        'post_id' => $postId
    ]);
})->register();
```

## Query Parameters

```php
// URL: /search?category=books&sort=price&order=asc

$category = request()->query('category');
$sort = request()->query('sort', 'name');
$order = request()->query('order', 'asc');

// Get all query parameters
$queryParams = request()->getQueryParams();
```

## POST Data

```php
// HTML form with POST
$name = request()->post('name');
$email = request()->post('email');

// All POST data
$postData = request()->getParsedBody();
```

## JSON Data

For API requests with JSON body:

```php
// Content-Type: application/json
// Body: {"name": "John", "email": "john@example.com"}

$data = request()->all(); // Automatically parses JSON
$name = $data['name'];
$email = $data['email'];
```

## File Uploads

```php
// HTML: <input type="file" name="avatar">
$file = request()->getUploadedFiles()['avatar'];

if ($file && $file->getError() === UPLOAD_ERR_OK) {
    $filename = $file->getClientFilename();
    $size = $file->getSize();
    $tmpName = $file->getStream()->getMetadata('uri');

    // Move to storage
    move_uploaded_file($tmpName, storage_path('app/' . $filename));
}
```

## Headers

```php
// Get single header
$contentType = request()->getHeaderLine('Content-Type');

// Check if header exists
if (request()->hasHeader('Authorization')) {
    $token = request()->getHeaderLine('Authorization');
}

// Get all headers
$headers = request()->getHeaders();
```

## Request Information

```php
$request = request();

// HTTP method
$method = $request->getMethod(); // GET, POST, PUT, etc.

// Is this an AJAX request?
$isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

// Request URI
$uri = (string) $request->getUri(); // /users/123

// Client IP
$ip = $request->getServerParams()['REMOTE_ADDR'] ?? '';

// User Agent
$userAgent = $request->getHeaderLine('User-Agent');

// Referer
$referer = $request->getHeaderLine('Referer');
```

## Request Attributes

Middleware can add attributes to requests:

```php
// In middleware
$request = $request->withAttribute('user_id', 123);

// In controller
$userId = request()->getAttribute('user_id');
```

## Content Type

```php
// Check request content type
$contentType = $request->getHeaderLine('Content-Type');

// Common types
if (str_contains($contentType, 'application/json')) {
    // JSON request
}

if (str_contains($contentType, 'multipart/form-data')) {
    // Form with files
}
```

## Request Validation

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;
use Marwa\Framework\Validation\ValidationException;

Router::post('/register', function() {
    try {
        $data = validate_request([
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        // Validation passed
        return Response::json(['success' => true, 'data' => $data]);

    } catch (ValidationException $e) {
        return Response::json([
            'success' => false,
            'errors' => $e->errors()->all()
        ], 422);
    }
})->register();
```

## Helper Functions

| Function | Description |
|----------|-------------|
| `request()` | Get current request |
| `request('key')` | Get input value |
| `request()->all()` | Get all input |
| `request()->query('key')` | Get query param |
| `request()->post('key')` | Get POST value |

## Next Steps

- [Responses](responses.md) - Send responses
- [Validation](validation.md) - Validate input
- [Middleware](middleware.md) - Filter requests
