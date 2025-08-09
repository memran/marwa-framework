<?php

declare(strict_types=1);

namespace Marwa\App\Exceptions;

use Exception;

final class FacadeException extends Exception
{
    /**
     * Create a new NotFoundException.
     *
     * @param string $id The ID of the service that was not found.
     */
    public function __construct(string $id)
    {
        parent::__construct("Facade with alias '{$id}' not found in the container.");
    }
}
