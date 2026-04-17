<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule;

use Marwa\Framework\Validation\ValidationRule\Contracts\RuleInterface;

abstract class AbstractRule implements RuleInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * @param string|array<string, mixed> $params
     */
    public function __construct(
        string|array $params = ''
    ) {
        $this->params = $this->parseParams($params);
    }

    public function params(): array
    {
        return $this->params;
    }

    protected function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    protected function getParamString(string $key, string $default = ''): string
    {
        return (string) ($this->params[$key] ?? $default);
    }

    protected function getParamStringOrNull(string $key): ?string
    {
        if (!array_key_exists($key, $this->params)) {
            return null;
        }

        return (string) $this->params[$key];
    }

    protected function humanizeField(string $field): string
    {
        $label = str_replace(['.', '_'], ' ', $field);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return ucfirst(trim($label));
    }

    /**
     * @param string $message
     * @param string $field
     * @param array<string, string> $attributes
     */
    protected function formatMessage(string $message, string $field, array $attributes): string
    {
        $attribute = $attributes[$field] ?? $this->humanizeField($field);
        $replacements = [
            ':field' => $field,
            ':attribute' => $attribute,
        ];

        foreach ($this->params as $key => $value) {
            $replacements[':' . $key] = (string) $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    protected function stringify(mixed $value): string
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
     * @param string|array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function parseParams(string|array $params): array
    {
        if (is_array($params)) {
            return $params;
        }

        if ($params === '') {
            return [];
        }

        /** @var array<string, mixed> $parsed */
        $parsed = [];
        $parts = array_map('trim', explode(',', $params));

        foreach ($parts as $index => $part) {
            if (str_contains($part, ':')) {
                [$key, $value] = array_pad(explode(':', $part, 2), 2, '');
                $parsed[$key] = $this->castValue($value);
            } else {
                $parsed[(string) $index] = $this->castValue($part);
            }
        }

        return $parsed;
    }

    private function castValue(string $value): mixed
    {
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        if (strtolower($value) === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
