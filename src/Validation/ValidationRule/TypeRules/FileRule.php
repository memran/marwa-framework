<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\TypeRules;

use Marwa\Framework\Validation\ValidationRule\AbstractRule;
use Psr\Http\Message\UploadedFileInterface;

final class FileRule extends AbstractRule
{
    public function name(): string
    {
        return 'file';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        return $value instanceof UploadedFileInterface;
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field must be a file upload.',
            $field,
            $attributes
        );
    }
}
