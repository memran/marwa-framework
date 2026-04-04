# Controller API

`Marwa\Framework\Controllers\Controller` is the framework base controller for application code.

## Methods

- `request(?string $key = null, mixed $default = null): mixed`
- `input(?string $key = null, mixed $default = null): mixed`
- `validate(array $rules, array $messages = [], array $attributes = [], ?ServerRequestInterface $request = null): array`
- `validated(FormRequest $request): array`
- `view(string $template, array $data = [], int $status = 200, array $headers = []): ResponseInterface`
- `render(string $template, array $data = []): string`
- `json(array|JsonSerializable $data, int $status = 200, array $headers = []): ResponseInterface`
- `response(string $body = '', int $status = 200, array $headers = []): ResponseInterface`
- `redirect(string $uri, int $status = 302, array $headers = []): ResponseInterface`
- `back(int $status = 302, array $headers = []): ResponseInterface`
- `flash(string $key, mixed $value): static`
- `withInput(?array $input = null): static`
- `withErrors(array|ErrorBag $errors): static`
- `old(?string $key = null, mixed $default = null): mixed`
- `session(?string $key = null, mixed $default = null): mixed`
- `unauthorized(string $message = 'Unauthorized'): ResponseInterface`
- `forbidden(string $message = 'Forbidden'): ResponseInterface`
- `abortIf(bool $condition, string $message = 'Forbidden', int $status = 403): ?ResponseInterface`
- `abortUnless(bool $condition, string $message = 'Forbidden', int $status = 403): ?ResponseInterface`
- `authorize(bool $condition, string $message = 'Forbidden'): ?ResponseInterface`

## Usage

```php
final class PostController extends Controller
{
    public function show(int $id): ResponseInterface
    {
        return $this->view('posts/show', [
            'post' => $id,
        ]);
    }
}
```

The controller assumes the framework application container is available, so request, session, view, and validation helpers resolve from the shared runtime.
