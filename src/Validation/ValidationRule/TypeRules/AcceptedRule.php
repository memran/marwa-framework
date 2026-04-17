<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\TypeRules;

use Marwa\Framework\Validation\Helpers\TypeValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;

final class AcceptedRule extends AbstractRule
{
    public function __construct(
        private TypeValidators $typeValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'accepted';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return $this->typeValidators->isAccepted($value);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field must be accepted.',
            $field,
            $attributes
        );
    }
}
