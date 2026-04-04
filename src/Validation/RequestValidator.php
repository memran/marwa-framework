<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation;

use Marwa\Entity\Contracts\RuleInterface;
use Marwa\Entity\Validation\ErrorBag;
use Marwa\Router\Contract\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class RequestValidator implements ValidatorInterface
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateInput(array $input, array $rules, array $messages = [], array $attributes = []): array
    {
        $validated = [];
        $errors = new ErrorBag();

        foreach ($this->normalizeRules($rules) as $field => $fieldRules) {
            $normalizedRules = $this->normalizeFieldRules($fieldRules);
            $exists = $this->hasValue($input, $field);
            $value = $this->getValue($input, $field, $exists);
            $value = $this->applyTransforms($value, $normalizedRules, $exists);

            $stopped = false;
            $failed = false;

            foreach ($normalizedRules as $rule) {
                if ($rule === 'bail') {
                    continue;
                }

                if ($rule === 'sometimes' && !$exists) {
                    continue 2;
                }

                if ($rule === 'nullable' && ($value === null || $value === '')) {
                    if ($exists) {
                        $this->setValue($validated, $field, null);
                    }

                    continue 2;
                }

                $error = $this->evaluateRule($field, $value, $exists, $rule, $input, $messages, $attributes);
                if ($error !== null) {
                    $errors->add($field, $error);
                    $failed = true;
                    $stopped = true;
                    break;
                }
            }

            if ($stopped) {
                continue;
            }

            if (!$failed && ($exists || $this->fieldHasDefault($normalizedRules))) {
                $value = $this->coerceValidatedValue($value, $normalizedRules);
                $this->setValue($validated, $field, $value);
            }
        }

        if ($errors->hasAny()) {
            throw new ValidationException($errors, $input);
        }

        return $validated;
    }

    public function validate(ServerRequestInterface $request, array $rules): array
    {
        return $this->validateRequest($request, $rules);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
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
        return $this->validateInput(
            $this->extractInput($request),
            $rules,
            $messages,
            $attributes
        );
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @return array<string, string|array<int, mixed>>
     */
    private function normalizeRules(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $field => $definition) {
            $normalized[(string) $field] = is_string($definition)
                ? $definition
                : (array) $definition;
        }

        return $normalized;
    }

    /**
     * @param string|array<int, mixed> $definition
     * @return array<int, mixed>
     */
    private function normalizeFieldRules(string|array $definition): array
    {
        if (is_string($definition)) {
            $rules = array_filter(array_map('trim', explode('|', $definition)));

            return array_values($rules);
        }

        $rules = [];

        foreach ($definition as $rule) {
            if (is_string($rule) && str_contains($rule, '|')) {
                foreach (explode('|', $rule) as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $rules[] = $part;
                    }
                }

                continue;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractInput(ServerRequestInterface $request): array
    {
        $input = [];
        $query = $request->getQueryParams();
        $input = array_replace_recursive($input, $query);

        $body = $request->getParsedBody();
        if (is_array($body)) {
            $input = array_replace_recursive($input, $body);
        }

        $files = $request->getUploadedFiles();
        if ($files !== []) {
            $input = array_replace_recursive($input, $files);
        }

        $params = $request->getAttribute('params');
        if (is_array($params)) {
            $input = array_replace_recursive($input, $params);
        }

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function hasValue(array $input, string $field): bool
    {
        $exists = false;
        $this->getValue($input, $field, $exists);

        return $exists;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function getValue(array $input, string $field, bool &$exists): mixed
    {
        $exists = false;
        $current = $input;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $exists = true;
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setValue(array &$data, string $field, mixed $value): void
    {
        $segments = explode('.', $field);
        $current = &$data;

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if ($index === array_key_last($segments)) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * @param array<int, mixed> $rules
     */
    private function fieldHasDefault(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'default:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     */
    private function evaluateRule(
        string $field,
        mixed $value,
        bool $exists,
        mixed $rule,
        array $input,
        array $messages,
        array $attributes
    ): ?string {
        if ($rule instanceof RuleInterface) {
            return $rule->validate($value, ['input' => $input, 'field' => $field]) ? null : $this->message(
                $field,
                'rule',
                'The :attribute field is invalid.',
                $messages,
                $attributes,
                ['value' => $value]
            );
        }

        if (!is_string($rule) && is_callable($rule)) {
            $result = $rule($value, $input, $field);

            if ($result === true || $result === null) {
                return null;
            }

            if (is_string($result) && $result !== '') {
                return $result;
            }

            return $this->message(
                $field,
                'rule',
                'The :attribute field is invalid.',
                $messages,
                $attributes,
                ['value' => $value]
            );
        }

        if (!is_string($rule) || $rule === '') {
            return null;
        }

        [$name, $parameterString] = array_pad(explode(':', $rule, 2), 2, '');
        $parameters = $parameterString === '' ? [] : array_map('trim', explode(',', $parameterString));

        return match ($name) {
            'required' => $this->isEmpty($value, true) ? $this->message($field, $name, 'The :attribute field is required.', $messages, $attributes, ['value' => $value]) : null,
            'present' => $exists ? null : $this->message($field, $name, 'The :attribute field must be present.', $messages, $attributes, ['value' => $value]),
            'filled' => $this->isEmpty($value, true) ? $this->message($field, $name, 'The :attribute field must not be empty.', $messages, $attributes, ['value' => $value]) : null,
            'string' => is_string($value) ? null : $this->message($field, $name, 'The :attribute field must be a string.', $messages, $attributes, ['value' => $value]),
            'integer' => $this->isInteger($value) ? null : $this->message($field, $name, 'The :attribute field must be an integer.', $messages, $attributes, ['value' => $value]),
            'numeric' => is_numeric($value) ? null : $this->message($field, $name, 'The :attribute field must be numeric.', $messages, $attributes, ['value' => $value]),
            'boolean' => $this->isBoolean($value) ? null : $this->message($field, $name, 'The :attribute field must be true or false.', $messages, $attributes, ['value' => $value]),
            'array' => is_array($value) ? null : $this->message($field, $name, 'The :attribute field must be an array.', $messages, $attributes, ['value' => $value]),
            'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false ? null : $this->message($field, $name, 'The :attribute field must be a valid email address.', $messages, $attributes, ['value' => $value]),
            'url' => filter_var((string) $value, FILTER_VALIDATE_URL) !== false ? null : $this->message($field, $name, 'The :attribute field must be a valid URL.', $messages, $attributes, ['value' => $value]),
            'accepted' => $this->isAccepted($value) ? null : $this->message($field, $name, 'The :attribute field must be accepted.', $messages, $attributes, ['value' => $value]),
            'declined' => $this->isDeclined($value) ? null : $this->message($field, $name, 'The :attribute field must be declined.', $messages, $attributes, ['value' => $value]),
            'file' => $value instanceof UploadedFileInterface ? null : $this->message($field, $name, 'The :attribute field must be a file upload.', $messages, $attributes, ['value' => $value]),
            'image' => $value instanceof UploadedFileInterface && $this->isImage($value) ? null : $this->message($field, $name, 'The :attribute field must be an image.', $messages, $attributes, ['value' => $value]),
            'confirmed' => $this->isConfirmed($field, $value, $input) ? null : $this->message($field, $name, 'The :attribute confirmation does not match.', $messages, $attributes, ['value' => $value]),
            'same' => $this->sameAs($field, $value, $input, $parameters[0] ?? '') ? null : $this->message($field, $name, 'The :attribute field must match :other.', $messages, $attributes, ['other' => $parameters[0] ?? '', 'value' => $value]),
            'in' => in_array((string) $value, $parameters, true) ? null : $this->message($field, $name, 'The :attribute field must be one of :values.', $messages, $attributes, ['values' => implode(', ', $parameters), 'value' => $value]),
            'min' => $this->compareMin($value, $parameters[0] ?? null) ? null : $this->message($field, $name, 'The :attribute field must be at least :min.', $messages, $attributes, ['min' => $parameters[0] ?? '', 'value' => $value]),
            'max' => $this->compareMax($value, $parameters[0] ?? null) ? null : $this->message($field, $name, 'The :attribute field must not be greater than :max.', $messages, $attributes, ['max' => $parameters[0] ?? '', 'value' => $value]),
            'between' => $this->compareBetween($value, $parameters[0] ?? null, $parameters[1] ?? null) ? null : $this->message($field, $name, 'The :attribute field must be between :min and :max.', $messages, $attributes, ['min' => $parameters[0] ?? '', 'max' => $parameters[1] ?? '', 'value' => $value]),
            'date' => $this->isDate($value) ? null : $this->message($field, $name, 'The :attribute field must be a valid date.', $messages, $attributes, ['value' => $value]),
            'date_format' => $this->matchesDateFormat($value, $parameters[0] ?? '') ? null : $this->message($field, $name, 'The :attribute field must match the format :format.', $messages, $attributes, ['format' => $parameters[0] ?? '', 'value' => $value]),
            'regex' => $this->matchesRegex($value, $parameters[0] ?? '') ? null : $this->message($field, $name, 'The :attribute field format is invalid.', $messages, $attributes, ['value' => $value]),
            'default' => null,
            'trim', 'lowercase', 'uppercase', 'nullable', 'sometimes', 'bail' => null,
            default => null,
        };
    }

    /**
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @param array<string, mixed> $context
     */
    private function message(
        string $field,
        string $rule,
        string $default,
        array $messages,
        array $attributes,
        array $context = []
    ): string {
        $message = $messages[$field . '.' . $rule]
            ?? $messages[$rule]
            ?? $default;

        $attribute = $attributes[$field] ?? $this->humanizeField($field);
        $replacements = array_merge([
            ':field' => $field,
            ':attribute' => $attribute,
        ], $context);

        if (array_key_exists('value', $replacements)) {
            $replacements[':value'] = $this->stringify($replacements['value']);
            unset($replacements['value']);
        }

        foreach ($replacements as $placeholder => $value) {
            $message = str_replace((string) $placeholder, (string) $value, $message);
        }

        return $message;
    }

    private function humanizeField(string $field): string
    {
        $label = str_replace(['.', '_'], ' ', $field);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return ucfirst(trim($label));
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'value';
    }

    /**
     * @param array<int, mixed> $rules
     */
    private function applyTransforms(mixed $value, array $rules, bool $exists): mixed
    {
        foreach ($rules as $rule) {
            if (!is_string($rule) || !str_contains($rule, ':')) {
                if ($rule === 'trim' && is_string($value)) {
                    $value = trim($value);
                }

                if ($rule === 'lowercase' && is_string($value)) {
                    $value = mb_strtolower($value);
                }

                if ($rule === 'uppercase' && is_string($value)) {
                    $value = mb_strtoupper($value);
                }

                continue;
            }

            [$name, $parameterString] = array_pad(explode(':', $rule, 2), 2, '');

            if ($name === 'default' && (!$exists || $value === null || $value === '')) {
                $value = $this->castDefault($parameterString);
            }
        }

        return $value;
    }

    private function castDefault(string $value): mixed
    {
        $value = trim($value);

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($value) ? ($value === (string) (int) $value ? (int) $value : (float) $value) : $value,
        };
    }

    private function isEmpty(mixed $value, bool $strict = false): bool
    {
        if ($value === null) {
            return true;
        }

        if ($value === '') {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        if ($strict) {
            return false;
        }

        return false;
    }

    /**
     * @param array<int, mixed> $rules
     */
    private function coerceValidatedValue(mixed $value, array $rules): mixed
    {
        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            $name = strtok($rule, ':');

            if ($name === 'boolean') {
                return $this->toBoolean($value);
            }

            if ($name === 'integer') {
                return $this->toInteger($value);
            }

            if ($name === 'numeric') {
                return $this->toNumeric($value);
            }
        }

        return $value;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function toInteger(mixed $value): int
    {
        return (int) $value;
    }

    private function toNumeric(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $numeric = (string) $value;

        return str_contains($numeric, '.') ? (float) $numeric : (int) $numeric;
    }

    private function isInteger(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
    }

    private function isBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value)) {
            return in_array($value, [0, 1], true);
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'], true);
        }

        return false;
    }

    private function isAccepted(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['yes', 'on', '1', 'true'], true);
    }

    private function isDeclined(mixed $value): bool
    {
        if (is_bool($value)) {
            return !$value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['no', 'off', '0', 'false'], true);
    }

    private function isImage(UploadedFileInterface $file): bool
    {
        $type = strtolower($file->getClientMediaType());

        return str_starts_with($type, 'image/');
    }

    /**
     * @param array<string, mixed> $input
     */
    private function isConfirmed(string $field, mixed $value, array $input): bool
    {
        $exists = false;
        $confirmation = $this->getValue($input, $field . '_confirmation', $exists);

        return $exists && (string) $confirmation === (string) $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function sameAs(string $field, mixed $value, array $input, string $other): bool
    {
        if ($other === '') {
            return false;
        }

        $exists = false;
        $otherValue = $this->getValue($input, $other, $exists);

        return $exists && (string) $otherValue === (string) $value;
    }

    private function compareMin(mixed $value, ?string $minimum): bool
    {
        if ($minimum === null || $minimum === '') {
            return true;
        }

        $min = (float) $minimum;

        return match (true) {
            is_string($value) => mb_strlen($value) >= $min,
            is_array($value) => count($value) >= $min,
            is_numeric($value) => (float) $value >= $min,
            $value instanceof UploadedFileInterface => ($value->getSize() ?? 0) >= $min,
            default => false,
        };
    }

    private function compareMax(mixed $value, ?string $maximum): bool
    {
        if ($maximum === null || $maximum === '') {
            return true;
        }

        $max = (float) $maximum;

        return match (true) {
            is_string($value) => mb_strlen($value) <= $max,
            is_array($value) => count($value) <= $max,
            is_numeric($value) => (float) $value <= $max,
            $value instanceof UploadedFileInterface => ($value->getSize() ?? 0) <= $max,
            default => false,
        };
    }

    private function compareBetween(mixed $value, ?string $minimum, ?string $maximum): bool
    {
        return $this->compareMin($value, $minimum) && $this->compareMax($value, $maximum);
    }

    private function isDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            new \DateTimeImmutable($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function matchesDateFormat(mixed $value, string $format): bool
    {
        if ($format === '') {
            return false;
        }

        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat($format, $value);

        return $date instanceof \DateTimeImmutable && $date->format($format) === $value;
    }

    private function matchesRegex(mixed $value, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (@preg_match($pattern, '') === false) {
            return false;
        }

        return is_scalar($value) || $value === null
            ? preg_match($pattern, (string) $value) === 1
            : false;
    }

    /**
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    public function normalize(array $rules): array
    {
        return $this->normalizeRules($rules);
    }
}
