<?php

namespace Marwa\App\Exceptions;

/**
 * Custom validation exception.
 */
class ValidationException extends InvalidArgumentException
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed.');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
