<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation\ValidationRule\TypeRules;

use Marwa\Framework\Validation\Helpers\TypeValidators;
use Marwa\Framework\Validation\ValidationRule\AbstractRule;
use Psr\Http\Message\UploadedFileInterface;

final class ImageRule extends AbstractRule
{
    public function __construct(
        private TypeValidators $typeValidators,
        string $params = ''
    ) {
        parent::__construct($params);
    }

    public function name(): string
    {
        return 'image';
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $context
     */
    public function validate(mixed $value, array $context): bool
    {
        if (!$value instanceof UploadedFileInterface) {
            return false;
        }

        return $this->typeValidators->isImage($value);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     */
    public function message(string $field, array $attributes): string
    {
        return $this->formatMessage(
            'The :attribute field must be an image.',
            $field,
            $attributes
        );
    }
}
