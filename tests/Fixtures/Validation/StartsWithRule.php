<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Validation;

use Marwa\Support\Validation\AbstractRule;

final class StartsWithRule extends AbstractRule
{
    public function name(): string
    {
        return 'starts_with';
    }

    public function validate(mixed $value, array $context): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return str_starts_with($value, $this->getParamString('0'));
    }

    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage('The :attribute must start with :0.', $field, $attributes);
    }
}
