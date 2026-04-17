<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\Helpers;

final class MessageFormatter
{
    /**
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @param array<string, mixed> $context
     */
    public function message(
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

    public function humanizeField(string $field): string
    {
        $label = str_replace(['.', '_'], ' ', $field);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return ucfirst(trim($label));
    }

    public function stringify(mixed $value): string
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
}
