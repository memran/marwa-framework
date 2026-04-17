<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\TransformRules;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class DefaultRule extends AbstractRule
{
    public function name(): string
    {
        return 'default';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return true;
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return '';
    }
}
