# Validation

The framework provides a thin request-validation layer on top of the existing router and entity tooling. Use `validate_request()` for quick checks or extend `Marwa\Framework\Validation\FormRequest` for reusable request objects.

## Helper-driven validation

```php
$data = validate_request([
    'title' => 'trim|required|string|min:3',
    'email' => 'required|email',
    'published' => 'boolean',
]);
```

The helper reads from the current PSR-7 request, validates the payload, and returns normalized data. Invalid requests throw a validation exception that the router middleware converts into a JSON `422` response or a redirect with flashed input.

## Form Request classes

```php
use Marwa\Framework\Validation\FormRequest;

final class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'published' => 'boolean',
        ];
    }

    protected function prepareForValidation(array $input): array
    {
        $input['title'] = trim((string) ($input['title'] ?? ''));

        return $input;
    }
}
```

Use `validated()` or `validate()` inside your controller:

```php
$data = (new StorePostRequest(request()))->validated();
```

## Flash data

Validation failures flash two session keys:

- `_old_input` for the submitted payload
- `errors` for the validation error bag

The `old()` helper reads back flashed input in your views.
