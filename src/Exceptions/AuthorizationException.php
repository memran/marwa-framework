<?php

declare(strict_types=1);

namespace Marwa\Framework\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    protected string $ability;

    protected mixed $resource;

    public function __construct(
        string $message = 'Unauthorized',
        string $ability = '',
        mixed $resource = null,
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->ability = $ability;
        $this->resource = $resource;
    }

    public function getAbility(): string
    {
        return $this->ability;
    }

    public function getResource(): mixed
    {
        return $this->resource;
    }
}
