# Responses

Responses are what your application sends back to the client. Marwa Framework supports various response types.

## Basic Responses

### HTML Response

```php
<?php

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

// Simple HTML
Router::get('/', function() {
    return Response::html('<h1>Welcome!</h1>');
})->register();

// With status code
Router::get('/old', function() {
    return Response::html('<h1>Page moved</h1>', 301);
})->register();
```

### JSON Response

```php
// Simple JSON
Router::get('/api/users', function() {
    return Response::json([
        'users' => [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ]
    ]);
})->register();

// With status code
Router::get('/api/users/{id}', function($id) {
    return Response::json([
        'user' => ['id' => $id, 'name' => 'John']
    ]);
})->register();

// Error response
Router::get('/api/error', function() {
    return Response::json([
        'error' => 'Something went wrong'
    ], 500);
})->register();
```

## Response with Headers

```php
Router::get('/download', function() {
    return Response::json(['file' => 'data'])
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('X-Custom-Header', 'value');
})->register();
```

## Redirect Responses

```php
// Simple redirect
Router::post('/login', function() {
    // Authentication logic...
    return Response::redirect('/dashboard');
})->register();

// Redirect with status
Router::post('/logout', function() {
    return Response::redirect('/login', 302);
})->register();

// Redirect back
Router::post('/contact', function() {
    return Response::redirect()->back();
})->register();

// Redirect with flash message
Router::post('/contact', function() {
    session()->flash('success', 'Message sent!');
    return Response::redirect('/contact');
})->register();
```

## View Responses

```php
// Render Twig template
Router::get('/', function() {
    return view('home', [
        'title' => 'Welcome',
        'users' => ['John', 'Jane']
    ]);
})->register();

// View with layout
Router::get('/about', function() {
    return view('about', [
        'content' => 'About page content'
    ]);
})->register();
```

## File Responses

### Download File

```php
Router::get('/download/{file}', function($file) {
    $path = storage_path('app/' . $file);

    if (!file_exists($path)) {
        return Response::html('<h1>File not found</h1>', 404);
    }

    return Response::file($path);
})->register();
```

### Stream File

```php
Router::get('/preview/{file}', function($file) {
    $path = storage_path('app/' . $file);

    return Response::stream(function() use ($path) {
        readfile($path);
    }, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $file . '"'
    ]);
})->register();
```

## Empty Responses

```php
// 204 No Content
Router::delete('/users/{id}', function($id) {
    // Delete user...
    return Response::html('', 204);
})->register();
```

## Response with Cookies

```php
Router::get('/login', function() {
    return Response::html('<h1>Login</h1>')
        ->withCookie('session_token', 'abc123', 3600, '/', '', true, true);
})->register();
```

## Response Helper Functions

### Response Creation

```php
// HTML response
return response('<h1>Hello</h1>');

// JSON response
return response()->json(['data' => $data]);

// Redirect
return response()->redirect('/path');
```

### Response Building

```php
$response = response()
    ->withStatus(200)
    ->withHeader('X-App-Version', '1.0.0')
    ->withBody($body);

return $response;
```

## Response Codes Reference

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 204 | No Content |
| 301 | Moved Permanently |
| 302 | Found (Redirect) |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Unprocessable Entity |
| 500 | Internal Server Error |

## Response Types Summary

| Type | Usage |
|------|-------|
| `Response::html()` | HTML content |
| `Response::json()` | JSON data |
| `Response::redirect()` | HTTP redirects |
| `Response::file()` | File downloads |
| `Response::stream()` | Streaming responses |
| `view()` | Twig templates |

## Next Steps

- [Views](views.md) - Twig templates
- [Requests](requests.md) - Handle input
- [Controllers](controllers.md) - Organize response logic
