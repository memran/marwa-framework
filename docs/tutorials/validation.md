# Validation

The framework provides a thin request-validation layer on top of the existing router and entity tooling. Use `validate_request()` for quick checks or extend `Marwa\Framework\Validation\FormRequest` for reusable request objects.

## Architecture

The validation system is built with a modular architecture:

```
Marwa\Framework\Validation\
├── ValidationRule\
│   ├── Contracts\RuleInterface.php     # Interface for all rules
│   ├── AbstractRule.php                 # Base class with common functionality
│   ├── TypeRules\                       # Type validation rules
│   ├── ComparisonRules\                 # Comparison rules
│   ├── DateRules\                      # Date validation rules
│   └── TransformRules\                  # Transform rules
├── Helpers\                             # Helper classes for validation
├── RuleRegistry.php                     # Manages rule registration
├── RequestValidator.php                # Main validator class
├── ValidationException.php             # Exception thrown on validation failure
└── FormRequest.php                     # Form request base class
```

## Built-in Validation Rules

### Type Rules
| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must be present and not empty | `required` |
| `present` | Field must be present in input | `present` |
| `filled` | Field must not be empty if present | `filled` |
| `string` | Value must be a string | `string` |
| `integer` | Value must be an integer | `integer` |
| `numeric` | Value must be numeric | `numeric` |
| `boolean` | Value must be true/false | `boolean` |
| `array` | Value must be an array | `array` |
| `email` | Valid email address | `email` |
| `url` | Valid URL | `url` |
| `file` | Uploaded file | `file` |
| `image` | Image file | `image` |
| `accepted` | Value must be accepted (yes, on, 1, true) | `accepted` |
| `declined` | Value must be declined (no, off, 0, false) | `declined` |

### Comparison Rules
| Rule | Description | Example |
|------|-------------|---------|
| `min` | Minimum value/length | `min:3` |
| `max` | Maximum value/length | `max:10` |
| `between` | Value between range | `between:1,10` |
| `in` | Value must be in list | `in:foo,bar,baz` |
| `same` | Value must match another field | `same:password_confirm` |
| `confirmed` | Field must have _confirmation match | `password` with `password_confirmation` |

### Date Rules
| Rule | Description | Example |
|------|-------------|---------|
| `date` | Valid date | `date` |
| `date_format` | Match specific format | `date_format:Y-m-d` |
| `regex` | Match regex pattern | `regex:/^[a-z]+$/` |

### Transform Rules
| Rule | Description | Example |
|------|-------------|---------|
| `trim` | Trim whitespace | `trim` |
| `lowercase` | Convert to lowercase | `lowercase` |
| `uppercase` | Convert to uppercase | `uppercase` |
| `default` | Default value if empty | `default:value` |

### Modifiers
| Rule | Description |
|------|-------------|
| `nullable` | Field can be null |
| `sometimes` | Validate only if present |
| `bail` | Stop on first validation failure |

## Helper-driven validation

```php
$data = validate_request([
    'title' => 'trim|required|string|min:3',
    'email' => 'required|email',
    'published' => 'boolean',
]);
```

The helper reads from the current PSR-7 request, validates the payload, and returns normalized data. Invalid requests throw a validation exception that the router middleware converts into a JSON `422` response or a redirect with flashed input.

## Using RequestValidator Directly

```php
use Marwa\Framework\Validation\RequestValidator;

$validator = new RequestValidator();

$data = $validator->validateInput([
    'title' => 'Hello World',
    'email' => 'test@example.com',
], [
    'title' => 'required|string|min:3',
    'email' => 'required|email',
]);
```

## Form Request classes

```php
use Marwa\Framework\Validation\FormRequest;

final class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'email' => 'required|email',
            'published' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'email address',
        ];
    }

    protected function prepareForValidation(array $input): array
    {
        $input['title'] = trim((string) ($input['title'] ?? ''));

        return $input;
    }

    protected function passedValidation(array $validated): array
    {
        // Add computed fields
        $validated['slug'] = strtolower(str_replace(' ', '-', $validated['title']));

        return $validated;
    }
}
```

Use `validated()` or `validate()` inside your controller:

```php
$data = (new StorePostRequest(request()))->validated();
// or
$data = (new StorePostRequest(request()))->validate();
```

## Custom Validation Rules

### Creating a Custom Rule

Create a class that implements `Marwa\Framework\Validation\ValidationRule\Contracts\RuleInterface`:

```php
<?php

namespace App\Validation\Rules;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class StrongPasswordRule extends AbstractRule
{
    public function name(): string
    {
        return 'strong_password';
    }

    public function validate(mixed $value, array $context): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return strlen($value) >= 8
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^A-Za-z0-9]/', $value);
    }

    public function message(string $field, array $attributes): string
    {
        return "The {$field} must be at least 8 characters with uppercase, number, and special character.";
    }
}
```

### Registering Custom Rules

#### Option 1: Register in RuleRegistry

```php
use Marwa\Framework\Validation\RuleRegistry;
use App\Validation\Rules\StrongPasswordRule;

$registry = app(RuleRegistry::class);
$registry->register('strong_password', StrongPasswordRule::class);
```

#### Option 2: Register via Method

```php
use Marwa\Framework\Validation\RequestValidator;
use App\Validation\Rules\StrongPasswordRule;

$validator = app(RequestValidator::class);

$data = $validator->validateInputWithCustomRules(
    $input,
    ['password' => 'required|strong_password'],
    [],
    [],
    ['strong_password' => StrongPasswordRule::class]  // Custom rules
);
```

### Using Callable Rules

You can also use closures as rules:

```php
$data = $validator->validateInput([
    'age' => 25,
], [
    'age' => [
        'required',
        function ($value, $input, $field) {
            if ($value < 18) {
                return 'You must be at least 18 years old.';
            }
            return true;
        },
    ],
]);
```

## Flash Data

Validation failures flash two session keys:

- `_old_input` for the submitted payload
- `errors` for the validation error bag

The `old()` helper reads back flashed input in your views:

```php
// Get all old input
$old = old();

// Get specific field
$title = old('title');

// Get with default
$name = old('name', 'Anonymous');
```

## Error Messages

Access validation errors from the exception:

```php
try {
    $data = validate_request($rules);
} catch (ValidationException $e) {
    $errors = $e->errors()->all();
    // ['title' => ['The title field is required.']]
}
```
