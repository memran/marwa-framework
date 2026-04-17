<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\DateRules;

use Marwa\Framework\Validation\Helpers\DateValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class RegexRule extends AbstractRule
{
    public function __construct(
        private DateValidators $dateValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'regex';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        $pattern = $this->getParamString('0', '');

        return $this->dateValidators->matchesRegex($value, $pattern);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field format is invalid.',
            $field,
            $attributes
        );
    }
}
