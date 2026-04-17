<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\TypeRules;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class UrlRule extends AbstractRule
{
    public function name(): string
    {
        return 'url';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return filter_var((string) $value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field must be a valid URL.',
            $field,
            $attributes
        );
    }
}
