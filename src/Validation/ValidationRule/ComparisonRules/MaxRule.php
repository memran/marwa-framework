<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\ComparisonRules;

use Marwa\Framework\Validation\Helpers\ComparisonValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class MaxRule extends AbstractRule
{
    public function __construct(
        private ComparisonValidators $comparisonValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'max';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $max = $this->getParamStringOrNull('0');

        return $this->comparisonValidators->compareMax($value, $max);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        $max = $this->getParamString('0', '');

        return $this->formatMessage(
            'The :attribute field must not be greater than :max.',
            $field,
            $attributes
        );
    }
}
