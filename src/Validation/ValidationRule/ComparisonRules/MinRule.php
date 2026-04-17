<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\ComparisonRules;

use Marwa\Framework\Validation\Helpers\ComparisonValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class MinRule extends AbstractRule
{
    public function __construct(
        private ComparisonValidators $comparisonValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'min';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $min = $this->getParamStringOrNull('0');

        return $this->comparisonValidators->compareMin($value, $min);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        $min = $this->getParamString('0', '');

        return $this->formatMessage(
            'The :attribute field must be at least :min.',
            $field,
            $attributes
        );
    }
}
