# Controllers

The framework ships a thin base controller at `Marwa\Framework\Controllers\Controller`. Application controllers generated with `make:controller` extend it by default.

## What It Gives You

- `view()` and `render()` for Twig rendering
- `json()` for API responses
- `redirect()` and `back()` for navigation
- `request()` and `input()` for request data
- `validate()` for quick rule-based validation
- `withInput()` and `withErrors()` for flash data
- `authorize()`, `abortIf()`, and `abortUnless()` for simple access checks

## Example

```php
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class PostController extends Controller
{
    public function store(): ResponseInterface
    {
        $data = $this->validate([
            'title' => 'required|string|min:3',
        ]);

        return $this->json([
            'message' => 'Post created',
            'data' => $data,
        ]);
    }
}
```

## Flash Helpers

Use the flash helpers before returning a redirect response:

```php
$this->withInput();
$this->withErrors(['title' => ['The title field is required.']]);

return $this->back();
```

The framework flashes `_old_input` and `errors` into the session so validation failures survive the redirect back flow.
