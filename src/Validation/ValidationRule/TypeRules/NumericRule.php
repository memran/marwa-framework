<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\TypeRules;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class NumericRule extends AbstractRule
{
    public function name(): string
    {
        return 'numeric';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return is_numeric($value);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field must be numeric.',
            $field,
            $attributes
        );
    }
}
