<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\ComparisonRules;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class InRule extends AbstractRule
{
    public function name(): string
    {
        return 'in';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $allowed = array_values($this->params);

        return in_array((string) $value, $allowed, true);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        $values = implode(', ', array_values($this->params));

        return $this->formatMessage(
            'The :attribute field must be one of :values.',
            $field,
            $attributes
        );
    }
}
