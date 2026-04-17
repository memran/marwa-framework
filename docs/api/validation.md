# Validation API

## `Marwa\Framework\Validation\RequestValidator`

Backward-compatible framework entrypoint for the support-backed validator.

Use `new RequestValidator()` or resolve it from the container. Internally it delegates to `Marwa\Support\Validation\RequestValidator`.

### Methods

#### `validateInput()`

```php
/**
 * @param array<string, mixed> $input
 * @param array<string, string|array<int, mixed>> $rules
 * @param array<string, string> $messages
 * @param array<string, string> $attributes
 * @return array<string, mixed>
 */
public function validateInput(
    array $input,
    array $rules,
    array $messages = [],
    array $attributes = []
): array
```

Validates raw input array against rules.

#### `validateRequest()`

```php
public function validateRequest(
    ServerRequestInterface $request,
    array $rules,
    array $messages = [],
    array $attributes = []
): array
```

Validates a PSR-7 request.

#### `validate()`

```php
public function validate(ServerRequestInterface $request, array $rules): array
```

Implements `ValidatorInterface` for router integration.

#### `validateInputWithCustomRules()`

```php
public function validateInputWithCustomRules(
    array $input,
    array $rules,
    array $messages = [],
    array $attributes = [],
    array $customRules = []
): array
```

Validates with custom rule classes supplied at runtime.

#### `normalize()`

```php
public function normalize(array $rules): array
```

Normalizes rules array format.

### Usage Examples

```php
// Basic usage
$data = app(RequestValidator::class)
    ->validateRequest(request(), [
        'title' => 'required|string|min:3',
        'email' => 'required|email',
    ]);

// With custom rules
$data = $validator->validateInputWithCustomRules(
    $input,
    ['password' => 'required|strong_password'],
    [],
    [],
    ['strong_password' => StrongPasswordRule::class]
);
```

For the full custom-rule walkthrough, see [Validation tutorial](../tutorials/validation.md).

---

## `Marwa\Support\Validation\RuleRegistry`

Manages registration and resolution of validation rules.

### Methods

#### `register()`

```php
public function register(string $name, string $class): void
```

Registers a single rule.

```php
$registry->register('strong_password', StrongPasswordRule::class);
```

#### `registerMany()`

```php
public function registerMany(array $rules): void
```

Registers multiple rules at once.

```php
$registry->registerMany([
    'strong_password' => StrongPasswordRule::class,
    'unique_email' => UniqueEmailRule::class,
]);
```

#### `get()`

```php
public function get(string $name): ?string
```

Gets the class name for a rule. Returns `null` if not found.

#### `has()`

```php
public function has(string $name): bool
```

Checks if a rule is registered.

#### `all()`

```php
public function all(): array
```

Returns all registered rules.

```php
// Returns: ['required' => RequiredRule::class, 'email' => EmailRule::class, ...]
$rules = $registry->all();
```

#### `resolve()`

```php
public function resolve(string $name, string $params = ''): ?RuleInterface
```

Creates a rule instance with parameters.

### Built-in Rules

The `RuleRegistry` automatically registers these rules on construction:

| Rule | Class |
|------|-------|
| required | `RequiredRule` |
| present | handled by `RequestValidator` |
| filled | handled by `RequestValidator` |
| string | `StringRule` |
| integer | `IntegerRule` |
| numeric | `NumericRule` |
| boolean | `BooleanRule` |
| array | `ArrayRule` |
| email | `EmailRule` |
| url | `UrlRule` |
| accepted | `AcceptedRule` |
| declined | `DeclinedRule` |
| file | `FileRule` |
| image | `ImageRule` |
| confirmed | `ConfirmedRule` |
| same | `SameRule` |
| in | `InRule` |
| min | `MinRule` |
| max | `MaxRule` |
| between | `BetweenRule` |
| date | `DateRule` |
| date_format | `DateFormatRule` |
| regex | `RegexRule` |
| default | handled by `RequestValidator` |
| trim | handled by `RequestValidator` |
| lowercase | handled by `RequestValidator` |
| uppercase | handled by `RequestValidator` |

---

## `Marwa\Support\Validation\Contracts\RuleInterface`

Interface for custom validation rules.

### Methods

#### `name()`

```php
public function name(): string
```

Returns the rule name (e.g., `'required'`, `'email'`).

#### `validate()`

```php
public function validate(mixed $value, array $context): bool
```

Validates the value. Return `true` for pass, `false` for fail.

**Context includes:**
- `field` - The field name being validated
- `input` - The full input array
- `exists` - Whether the field exists in input

#### `message()`

```php
public function message(string $field, array $attributes): string
```

Returns the error message for validation failure.

#### `params()`

```php
public function params(): array
```

Returns the parsed rule parameters.

---

## `Marwa\Support\Validation\AbstractRule`

Base class for creating custom rules. Provides common functionality.

### Constructor

```php
public function __construct(string|array $params = '')
```

### Protected Methods

#### `getParam()`

```php
protected function getParam(string $key, mixed $default = null): mixed
```

Gets a named parameter.

#### `getParamString()`

```php
protected function getParamString(string $key, string $default = ''): string
```

Gets a parameter as string.

#### `getParamInt()`

```php
protected function getParamInt(string $key, int $default = 0): int
```

Gets a parameter as integer.

#### `formatMessage()`

```php
protected function formatMessage(
    string $message,
    string $field,
    array $attributes
): string
```

Formats error message with placeholders.

### Example Custom Rule

```php
use Marwa\Support\Validation\AbstractRule;

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
            && preg_match('/[0-9]/', $value);
    }

    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute must be at least 8 characters with uppercase and number.',
            $field,
            $attributes
        );
    }
}
```

---

## `Marwa\Framework\Validation\FormRequest`

Extend this class when you want reusable validation objects with hooks.

### Methods

| Method | Description |
|--------|-------------|
| `rules(): array` | Define validation rules |
| `messages(): array` | Custom error messages |
| `attributes(): array` | Custom attribute names |
| `authorize(): bool` | Authorization check |
| `prepareForValidation(array $input): array` | Transform input before validation |
| `passedValidation(array $validated): array` | Transform validated data |
| `validated(): array` | Get validated data (performs validation) |
| `validate(): array` | Alias for `validated()` |
| `safe(): array` | Alias for `validated()` |

### Example

```php
use Marwa\Framework\Validation\FormRequest;

final class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
        ];
    }

    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(array $input): array
    {
        $input['title'] = trim($input['title'] ?? '');
        return $input;
    }

    protected function passedValidation(array $validated): array
    {
        $validated['slug'] = str_slug($validated['title']);
        return $validated;
    }
}
```

### Usage in Controller

```php
public function store(StorePostRequest $request): Response
{
    $data = $request->validated(); // or $request->validate()
    
    // $data includes 'slug' added in passedValidation()
}
```

---

## `Marwa\Support\Validation\ValidationException`

Thrown when validation fails.

### Properties

| Property | Type | Description |
|----------|------|-------------|
### Methods

#### `errors()`

```php
public function errors(): ErrorBag
```

Returns the error bag.

#### `input()`

```php
public function input(): array
```

Returns the original input that failed validation.

#### `toResponse()`

```php
public function toResponse(ServerRequestInterface $request): ResponseInterface
```

Converts to appropriate response (JSON for API, redirect for HTML).

### Session Keys

- `_old_input` - Flashes the submitted payload
- `errors` - Flashes the validation error bag

---

## Helper Functions

### `validate_request()`

```php
/**
 * @param array<string, string|array<int, mixed>> $rules
 * @param array<string, string> $messages
 * @param array<string, string> $attributes
 * @param ServerRequestInterface|null $request
 * @return array<string, mixed>
 */
function validate_request(
    array $rules,
    array $messages = [],
    array $attributes = [],
    ?ServerRequestInterface $request = null
): array
```

Quick validation helper using the current request.

### `old()`

```php
function old(?string $key = null, mixed $default = null): mixed
```

Retrieves flashed old input from session.

---

## Error Bag Methods

The `ErrorBag` object (from `Marwa\Entity\Validation\ErrorBag`) provides:

```php
// Check if has errors
$errors->hasAny();

// Get all errors as flat array
$errors->all();

// Get first error for a field
$errors->first('email');

// Iterate over errors
foreach ($errors as $field => $messages) {
    // $field = 'email'
    // $messages = ['The email field is required.']
}
```
