<?php

declare(strict_types=1);

namespace Marwa\App\Exceptions;

final class InvalidArgumentException extends \RuntimeException
{
    /**
     * Create a new NotFoundException.
     *
     * @param string $id The ID of the service that was not found.
     */
    public function __construct(string $id)
    {
        parent::__construct("Service with ID '{$id}' not found in the container.");
    }
}
