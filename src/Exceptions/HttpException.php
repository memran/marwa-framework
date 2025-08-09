<?php

declare(strict_types=1);

namespace Marwa\App\Exceptions;

use Exception;

final class HttpException extends Exception
{
    /**
     * Create a new NotFoundException.
     *
     * @param string $id The ID of the service that was not found.
     */
    public function __construct(int $code, string $message)
    {
        parent::__construct("HTTP error id '{$code}' with '{$message}'");
    }
}
