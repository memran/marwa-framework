# Controllers

`Marwa\Framework\Controllers\Controller` is the base class for HTTP controllers in the framework. It gives you a compact set of helpers for the most common controller jobs: reading request data, validating input, returning HTML or JSON responses, redirecting, flashing session data, and stopping execution with authorization-style guards.

Use it when you want controller code to stay focused on application behavior instead of response plumbing.

## When To Extend `Controller`

Extend the base controller when your class handles HTTP requests and you want access to framework helpers such as:

- `request()` and `input()` for request access
- `validate()` and `validated()` for request validation
- `view()` and `render()` for Twig output
- `json()` and `response()` for direct responses
- `redirect()` and `back()` for navigation flows
- `flash()`, `withInput()`, and `withErrors()` for session-backed form UX
- `authorize()`, `abortIf()`, and `abortUnless()` for simple guard clauses

If a class does not need HTTP concerns, keep it as a regular service instead of making it a controller.

## Basic Example

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class PostController extends Controller
{
    public function show(int $id): ResponseInterface
    {
        return $this->view('posts/show', [
            'postId' => $id,
        ]);
    }
}
```

The controller method returns a PSR-7 `ResponseInterface`. In most cases, that response will come from one of the inherited helper methods.

## Reading Request Data

Use `request()` when you need the full PSR-7 request object. Use `input()` when you only need submitted input values.

```php
public function store(): ResponseInterface
{
    $request = $this->request();
    $title = $this->input('title');
    $status = $this->input('status', 'draft');

    return $this->json([
        'method' => $request->getMethod(),
        'title' => $title,
        'status' => $status,
    ]);
}
```

Behavior summary:

- `request()` with no argument returns the current `ServerRequestInterface`
- `request('field')` returns a single input value through the input helper
- `input()` with no argument returns all input data as an array
- `input('field', $default)` returns one value with an optional default

## Returning Views And HTML

Use `view()` when you want to render a Twig template and immediately return an HTML response. Use `render()` when you only need the rendered string, for example when building a larger custom response.

```php
public function index(): ResponseInterface
{
    return $this->view('posts/index', [
        'posts' => [
            ['title' => 'First post'],
            ['title' => 'Second post'],
        ],
    ]);
}
```

You can also control status codes and headers:

```php
return $this->view('posts/index', ['posts' => $posts], 200, [
    'X-Controller' => 'PostController',
]);
```

If you need plain HTML without a template:

```php
return $this->response('<h1>Service unavailable</h1>', 503);
```

## Returning JSON

Use `json()` for API endpoints, AJAX handlers, or endpoints that should return machine-readable output.

```php
public function apiShow(int $id): ResponseInterface
{
    return $this->json([
        'data' => [
            'id' => $id,
            'title' => 'Example post',
        ],
    ]);
}
```

You can pass a custom status code and headers:

```php
return $this->json(['message' => 'Created'], 201, [
    'X-Resource' => 'posts',
]);
```

## Validating Input

The `validate()` helper is the fastest way to validate incoming request data inside a controller.

```php
public function store(): ResponseInterface
{
    $data = $this->validate([
        'title' => 'required|string|min:3',
        'body' => 'required|string',
        'published' => 'nullable|boolean',
    ]);

    return $this->json([
        'message' => 'Post created',
        'data' => $data,
    ], 201);
}
```

`validate()` returns only the validated payload as an array. It also accepts custom messages, custom attribute labels, and an optional request instance when you need to validate a different request object.

If your project uses a dedicated form request object, use `validated()`:

```php
use App\Http\Requests\StorePostRequest;

public function store(StorePostRequest $request): ResponseInterface
{
    $data = $this->validated($request);

    return $this->json(['data' => $data], 201);
}
```

## Redirects And Post-Redirect-Get

Use `redirect()` when you know the target URI and `back()` when you want to send the user to the previous page.

```php
public function destroy(int $id): ResponseInterface
{
    // Delete the record...

    return $this->redirect('/posts');
}
```

`back()` uses the request `Referer` header when it is available, but only when the target is safe. Relative paths are allowed, and absolute URLs are only used when they match the current request origin. If the header is missing or unsafe, it falls back to the current request URI, and finally to `/`.

## Flashing Form State

The base controller includes session flash helpers for common form flows. This is especially useful when validation fails and you want to redirect back with the previous input and the error bag.

```php
public function store(): ResponseInterface
{
    if (trim((string) $this->input('title', '')) === '') {
        $this->withInput();
        $this->withErrors([
            'title' => ['The title field is required.'],
        ]);

        return $this->back();
    }

    return $this->redirect('/posts');
}
```

Available helpers:

- `flash($key, $value)` stores one flash value
- `withInput()` flashes `_old_input`
- `withErrors()` flashes validation-style errors
- `old()` reads flashed input values
- `session()` reads session values directly

Example of reading old input in a later request:

```php
$title = $this->old('title', '');
```

## Authorization And Guard Clauses

The controller does not implement a full policy system by itself, but it does provide lightweight response helpers for common permission checks.

Use `authorize()` when a condition must be true:

```php
public function edit(int $id): ResponseInterface
{
    if ($response = $this->authorize(user()?->isAdmin() === true)) {
        return $response;
    }

    return $this->view('posts/edit', ['id' => $id]);
}
```

Use `abortIf()` or `abortUnless()` when you want the same pattern with explicit intent:

```php
if ($response = $this->abortUnless($post->isEditable(), 'Post is locked', 403)) {
    return $response;
}
```

These helpers return `null` when execution may continue, or a ready-made `ResponseInterface` when the request should stop immediately.

## Practical CRUD Example

This example combines the most common helpers in one controller method:

```php
public function update(int $id): ResponseInterface
{
    if ($response = $this->authorize(user()?->can('posts.update') === true)) {
        return $response;
    }

    $data = $this->validate([
        'title' => 'required|string|min:3',
        'body' => 'required|string',
    ]);

    // Update the post...

    $this->flash('success', 'Post updated successfully.');

    return $this->redirect('/posts/' . $id);
}
```

This style keeps controller methods short and readable:

- authorize early
- validate next
- perform the application action
- return one clear response

## Good Practices

- Keep controllers thin. Move business rules into services, actions, or domain classes.
- Return responses from the helper methods instead of manually building response objects unless you need custom behavior.
- Use `validate()` for straightforward endpoints and dedicated request classes when validation rules become large or reusable.
- Prefer early returns for guard checks and validation-related redirects.
- Use `json()` consistently for API endpoints and `view()` for browser-facing pages.

## Related Reading

- [Controller API](../api/controllers.md)
- [Validation Tutorial](./validation.md)
- [Views Tutorial](./views.md)
