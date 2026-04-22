<?php

declare(strict_types=1);

namespace Marwa\Support;

final class Json
{
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | $flags, $depth);
    }

    public static function decode(string $json, bool $assoc = false, int $depth = 512, int $flags = 0): mixed
    {
        return json_decode($json, $assoc, $depth, JSON_THROW_ON_ERROR | $flags);
    }
}

final class Url
{
    public static function isAbsolute(string $url): bool
    {
        return self::scheme($url) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function parse(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || self::scheme($url) === null) {
            throw new \InvalidArgumentException(sprintf('Invalid URL: %s', $url));
        }

        return $parts;
    }

    public static function scheme(string $url): ?string
    {
        $parts = parse_url($url);

        return is_array($parts) && array_key_exists('scheme', $parts)
            ? (string) $parts['scheme']
            : null;
    }

    public static function host(string $url): ?string
    {
        $parts = parse_url($url);

        return is_array($parts) && array_key_exists('host', $parts)
            ? (string) $parts['host']
            : null;
    }

    public static function port(string $url): ?int
    {
        $parts = parse_url($url);

        return is_array($parts) && array_key_exists('port', $parts)
            ? (int) $parts['port']
            : null;
    }
}

namespace Marwa\Support\Validation\Contracts;

interface RuleInterface
{
    public function name(): string;

    /**
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool;

    /**
     * @param array<string, mixed> $attributes
     */
    public function message(string $field, array $attributes): string;
}

namespace Marwa\Support\Validation;

use Marwa\Support\Validation\Contracts\RuleInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ErrorBag
{
    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    public function add(string $field, string $message): void
    {
        $this->errors[$field] ??= [];
        $this->errors[$field][] = $message;
    }

    public function has(string $field): bool
    {
        return !empty($this->errors[$field] ?? []);
    }

    public function hasAny(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return list<string>
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->errors;
    }
}

abstract class AbstractRule implements RuleInterface
{
    /**
     * @var array<int|string, mixed>
     */
    protected array $params = [];

    protected string $defaultMessage = 'The :field field is invalid.';

    /**
     * @param array<int|string, mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function params(): array
    {
        return $this->params;
    }

    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage($this->defaultMessage, $field, $attributes);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return true;
    }

    public function name(): string
    {
        return static::class;
    }

    public function getParamString(string $key, string $default = ''): string
    {
        $value = $this->params[$key] ?? $this->params[(int) $key] ?? $default;

        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function formatMessage(string $template, string $field, array $attributes = []): string
    {
        $label = isset($attributes[$field]) && is_string($attributes[$field])
            ? $attributes[$field]
            : $field;

        $replacements = array_merge([
            'field' => $label,
            'attribute' => $label,
        ], $this->params, $attributes);

        foreach ($replacements as $key => $value) {
            $template = str_replace(':' . (string) $key, $this->stringifyValue($value), $template);
        }

        return $template;
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode(', ', array_map($this->stringifyValue(...), $value));
        }

        return '';
    }
}

final class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly ErrorBag $errors,
        private readonly array $input = [],
        string $message = 'The given data was invalid.',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->input;
    }
}

final class RuleRegistry
{
    /**
     * @var array<string, callable|class-string<RuleInterface>|RuleInterface>
     */
    private array $resolvers = [];

    public function __construct() {}

    /**
     * @param callable|class-string<RuleInterface>|RuleInterface $resolver
     */
    public function register(string $name, callable|string|RuleInterface $resolver): void
    {
        $this->resolvers[strtolower($name)] = $resolver;
    }

    /**
     * @param array<string, callable|string|RuleInterface> $rules
     */
    public function registerMany(array $rules): void
    {
        foreach ($rules as $name => $resolver) {
            if ($name === '') {
                continue;
            }

            $this->register($name, $resolver);
        }
    }

    public function resolve(string $name, string $params = ''): ?RuleInterface
    {
        $key = strtolower(trim($name));
        if ($key === '') {
            return null;
        }

        $resolver = $this->resolvers[$key] ?? null;

        if ($resolver !== null) {
            return $this->resolveRegistered($resolver, $params);
        }

        return $this->makeBuiltinRule($key, $this->parseParameters($params));
    }

    /**
     * @param array<int, mixed> $params
     * @param array<string, mixed> $providers
     */
    public function make(string $name, array $params = [], array $providers = []): RuleInterface
    {
        $resolved = $this->resolve($name, $this->stringifyParameters($params));

        if ($resolved !== null) {
            return $resolved;
        }

        throw new \InvalidArgumentException(sprintf('Unknown rule: %s', $name));
    }

    /**
     * @param callable|class-string<RuleInterface>|RuleInterface $resolver
     * @param string $params
     */
    private function resolveRegistered(callable|string|RuleInterface $resolver, string $params): ?RuleInterface
    {
        $arguments = $this->parseParameters($params);

        if ($resolver instanceof RuleInterface) {
            $this->applyParams($resolver, $arguments);

            return $resolver;
        }

        if (is_string($resolver)) {
            if (!class_exists($resolver)) {
                return null;
            }

            $rule = new $resolver();
            if (!$rule instanceof RuleInterface) {
                return null;
            }

            $this->applyParams($rule, $arguments);

            return $rule;
        }

        $rule = $resolver($arguments, []);

        return $rule instanceof RuleInterface ? $rule : null;
    }

    /**
     * @param array<int, mixed> $params
     */
    private function applyParams(RuleInterface $rule, array $params): void
    {
        if ($rule instanceof AbstractRule) {
            $rule->setParams($params);
        }
    }

    /**
     * @param array<int, mixed> $params
     */
    private function stringifyParameters(array $params): string
    {
        if ($params === []) {
            return '';
        }

        return implode(',', array_map(static fn (mixed $value): string => (string) $value, $params));
    }

    /**
     * @return array<int, mixed>
     */
    private function parseParameters(string $params): array
    {
        if ($params === '') {
            return [];
        }

        return array_map('trim', explode(',', $params));
    }

    /**
     * @param array<int, mixed> $params
     */
    private function makeBuiltinRule(string $name, array $params): ?RuleInterface
    {
        return match ($name) {
            'required' => new BuiltinRule(
                'required',
                static fn (mixed $value): bool => !(
                    $value === null
                    || (is_string($value) && trim($value) === '')
                    || (is_array($value) && $value === [])
                ),
                'The :field field is required.'
            ),
            'string' => new BuiltinRule('string', static fn (mixed $value): bool => $value === null || is_string($value), 'The :field must be a string.'),
            'integer' => new BuiltinRule('integer', static fn (mixed $value): bool => $value === null || filter_var($value, FILTER_VALIDATE_INT) !== false, 'The :field must be an integer.'),
            'boolean' => new BuiltinRule('boolean', static fn (mixed $value): bool => $value === null || is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true), 'The :field must be true or false.'),
            'min' => new BuiltinRule(
                'min',
                static function (mixed $value) use ($params): bool {
                    $min = (int) ($params[0] ?? 0);

                    if (is_string($value)) {
                        return mb_strlen($value) >= $min;
                    }

                    if (is_array($value)) {
                        return count($value) >= $min;
                    }

                    return is_numeric($value) ? $value >= $min : false;
                },
                'The :field must be at least :0 characters.',
                $params
            ),
            'max' => new BuiltinRule(
                'max',
                static function (mixed $value) use ($params): bool {
                    $max = (int) ($params[0] ?? 0);

                    if (is_string($value)) {
                        return mb_strlen($value) <= $max;
                    }

                    if (is_array($value)) {
                        return count($value) <= $max;
                    }

                    return is_numeric($value) ? $value <= $max : false;
                },
                'The :field may not be greater than :0 characters.',
                $params
            ),
            'email' => new BuiltinRule('email', static fn (mixed $value): bool => $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false, 'The :field must be a valid email address.'),
            'url' => new BuiltinRule('url', static fn (mixed $value): bool => $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false, 'The :field must be a valid URL.'),
            'ip' => new BuiltinRule('ip', static fn (mixed $value): bool => $value === null || filter_var($value, FILTER_VALIDATE_IP) !== false, 'The :field must be a valid IP address.'),
            'date' => new BuiltinRule('date', static fn (mixed $value): bool => $value === null || strtotime((string) $value) !== false, 'The :field must be a valid date.'),
            'regex' => new BuiltinRule(
                'regex',
                static function (mixed $value) use ($params): bool {
                    $pattern = (string) ($params[0] ?? '');

                    return $pattern !== '' && is_string($value) && preg_match($pattern, $value) === 1;
                },
                'The :field format is invalid.',
                $params
            ),
            'in' => new BuiltinRule(
                'in',
                static function (mixed $value) use ($params): bool {
                    return in_array($value, $params, true);
                },
                'The :field must be one of the allowed values.',
                $params
            ),
            'confirmed' => new BuiltinRule(
                'confirmed',
                static function (mixed $value, array $context) use ($params): bool {
                    $confirmField = (string) ($params[0] ?? '');
                    $input = $context['input'] ?? [];

                    if (!is_array($input) || $confirmField === '') {
                        return false;
                    }

                    return array_key_exists($confirmField, $input) && $input[$confirmField] === $value;
                },
                'The :field confirmation does not match.',
                $params
            ),
            'nullable' => new BuiltinRule('nullable', static fn (): bool => true, 'The :field field is optional.'),
            default => null,
        };
    }
}

final class BuiltinRule extends AbstractRule
{
    /**
     * @param array<int, mixed> $params
     */
    public function __construct(
        private readonly string $ruleName,
        private readonly \Closure $validator,
        string $message,
        array $params = []
    ) {
        $this->defaultMessage = $message;
        $this->params = $params;
    }

    public function name(): string
    {
        return $this->ruleName;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return ($this->validator)($value, $context);
    }
}

final class RequestValidator
{
    public function __construct(private readonly RuleRegistry $registry) {}

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
    ): array {
        $validated = $input;
        $errors = new ErrorBag();
        $normalized = $this->normalize($rules);

        foreach ($normalized as $field => $fieldRules) {
            $exists = array_key_exists($field, $validated);
            $value = $validated[$field] ?? null;
            $bail = false;

            foreach ($fieldRules as $rule) {
                if (!is_string($rule) || $rule === '') {
                    if ($rule instanceof RuleInterface && !$this->applyRuleObject($field, $rule, $value, $errors, $validated, $attributes)) {
                        break;
                    }

                    continue;
                }

                [$name, $params] = $this->splitRule($rule);

                switch ($name) {
                    case 'sometimes':
                        if (!$exists) {
                            continue 3;
                        }
                        continue 2;
                    case 'nullable':
                        if ($value === null || $value === '') {
                            continue 3;
                        }
                        continue 2;
                    case 'trim':
                        if (is_string($value)) {
                            $value = trim($value);
                        }
                        continue 2;
                    case 'lowercase':
                        if (is_string($value)) {
                            $value = mb_strtolower($value);
                        }
                        continue 2;
                    case 'uppercase':
                        if (is_string($value)) {
                            $value = mb_strtoupper($value);
                        }
                        continue 2;
                    case 'default':
                        if (!$exists || $value === null || $value === '') {
                            $value = $params[0] ?? null;
                            $exists = true;
                        }
                        continue 2;
                    case 'bail':
                        $bail = true;
                        continue 2;
                }

                $resolved = $this->registry->resolve($name, $params !== [] ? implode(',', $params) : '');

                if ($resolved === null) {
                    continue;
                }

                if (!$resolved->validate($value, ['input' => $validated, 'field' => $field])) {
                    $errors->add($field, $resolved->message($field, $attributes));

                    if ($bail) {
                        break;
                    }

                    break;
                }

                $value = $this->castValue($name, $value);
            }

            if (!$errors->has($field) && $exists) {
                $validated[$field] = $value;
            }
        }

        if ($errors->hasAny()) {
            throw new ValidationException($errors, $input);
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateRequest(
        ServerRequestInterface $request,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        $input = array_merge(
            $request->getQueryParams(),
            is_array($request->getParsedBody()) ? $request->getParsedBody() : []
        );

        return $this->validateInput($input, $rules, $messages, $attributes);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @return array<string, string|array<int, mixed>>
     */
    public function normalize(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $field => $fieldRules) {
            if (is_array($fieldRules)) {
                $normalized[$field] = [];

                foreach ($fieldRules as $rule) {
                    foreach ($this->normalizeRuleValue($rule) as $normalizedRule) {
                        $normalized[$field][] = $normalizedRule;
                    }
                }

                continue;
            }

            $normalized[$field] = $this->normalizeRuleValue($fieldRules);
        }

        return $normalized;
    }

    /**
     * @param mixed $rule
     * @return array<int, string|RuleInterface>
     */
    private function normalizeRuleValue(mixed $rule): array
    {
        if ($rule instanceof RuleInterface) {
            return [$rule];
        }

        if (!is_string($rule) || $rule === '') {
            return [];
        }

        return array_filter(array_map('trim', explode('|', $rule)), static fn (string $value): bool => $value !== '');
    }

    /**
     * @return array{0:string,1:array<int, string>}
     */
    private function splitRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [strtolower($rule), []];
        }

        [$name, $params] = explode(':', $rule, 2);

        return [strtolower(trim($name)), array_values(array_filter(array_map('trim', explode(',', $params)), static fn (string $value): bool => $value !== ''))];
    }

    private function castValue(string $rule, mixed $value): mixed
    {
        return match ($rule) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'integer' => is_numeric($value) ? (int) $value : $value,
            default => $value,
        };
    }

    /**
     * @param array<string, mixed> $validated
     * @param array<string, string> $attributes
     */
    private function applyRuleObject(string $field, RuleInterface $rule, mixed &$value, ErrorBag $errors, array $validated, array $attributes): bool
    {
        if (!$rule->validate($value, ['input' => $validated, 'field' => $field])) {
            $errors->add($field, $rule->message($field, $attributes));

            return false;
        }

        $value = $this->castValue($rule->name(), $value);

        return true;
    }
}
