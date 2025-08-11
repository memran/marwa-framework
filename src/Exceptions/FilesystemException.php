<?php

declare(strict_types=1);

namespace Marwa\App\Exceptions;

use RuntimeException;

class FilesystemException extends RuntimeException
{
    /** @var string|null */
    private ?string $path;

    public function __construct(string $message, ?string $path = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->path = $path;
    }

    public function path(): ?string
    {
        return $this->path;
    }
}
