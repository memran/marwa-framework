<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\DateRules;

use Marwa\Framework\Validation\Helpers\DateValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class DateRule extends AbstractRule
{
    public function __construct(
        private DateValidators $dateValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'date';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return $this->dateValidators->isDate($value);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field must be a valid date.',
            $field,
            $attributes
        );
    }
}
