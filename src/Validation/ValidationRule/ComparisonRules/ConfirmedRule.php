<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\ComparisonRules;

use Marwa\Framework\Validation\Helpers\ComparisonValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class ConfirmedRule extends AbstractRule
{
    public function __construct(
        private ComparisonValidators $comparisonValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'confirmed';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $input = $context['input'] ?? [];

        return $this->comparisonValidators->isConfirmed($context['field'] ?? '', $value, $input);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute confirmation does not match.',
            $field,
            $attributes
        );
    }
}
