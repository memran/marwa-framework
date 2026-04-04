# Validation API

## `Marwa\Framework\Validation\RequestValidator`

Validates request data against rule strings or custom callables.

```php
$data = app(\Marwa\Framework\Validation\RequestValidator::class)
    ->validateRequest(request(), [
        'title' => 'required|string|min:3',
    ]);
```

Supported common rules include `required`, `string`, `integer`, `boolean`, `numeric`, `array`, `email`, `url`, `min`, `max`, `between`, `confirmed`, `same`, `in`, `date`, `date_format`, `regex`, `file`, `image`, `accepted`, and `declined`.

## `Marwa\Framework\Validation\FormRequest`

Extend this class when you want reusable validation objects with hooks.

Methods:

- `rules(): array`
- `messages(): array`
- `attributes(): array`
- `authorize(): bool`
- `prepareForValidation(array $input): array`
- `passedValidation(array $validated): array`
- `validated(): array`

## `Marwa\Framework\Validation\ValidationException`

Thrown when validation fails. The router middleware converts it into a `422` JSON response or an HTML redirect with flashed session data.

### Session keys

- `_old_input`
- `errors`
