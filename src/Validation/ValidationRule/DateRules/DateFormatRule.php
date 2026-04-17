<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\DateRules;

use Marwa\Framework\Validation\Helpers\DateValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class DateFormatRule extends AbstractRule
{
    public function __construct(
        private DateValidators $dateValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'date_format';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $format = $this->getParamString('0', '');

        return $this->dateValidators->matchesDateFormat($value, $format);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        $format = $this->getParamString('0', '');

        return $this->formatMessage(
            'The :attribute field must match the format :format.',
            $field,
            $attributes
        );
    }
}
