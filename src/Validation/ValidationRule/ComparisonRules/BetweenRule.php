<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\ComparisonRules;

use Marwa\Framework\Validation\Helpers\ComparisonValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class BetweenRule extends AbstractRule
{
    public function __construct(
        private ComparisonValidators $comparisonValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'between';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $min = $this->getParamStringOrNull('0');
        $max = $this->getParamStringOrNull('1');

        return $this->comparisonValidators->compareBetween($value, $min, $max);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        $min = $this->getParamString('0', '');
        $max = $this->getParamString('1', '');

        return $this->formatMessage(
            'The :attribute field must be between :min and :max.',
            $field,
            $attributes
        );
    }
}
