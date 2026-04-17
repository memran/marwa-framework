<?php

declare(strict_types=1);

namespace Marwa\Framework\Validation;

use Marwa\Entity\Contracts\RuleInterface as EntityRuleInterface;
use Marwa\Entity\Validation\ErrorBag;
use Marwa\Framework\Validation\Helpers\ComparisonValidators;
use Marwa\Framework\Validation\Helpers\DateValidators;
use Marwa\Framework\Validation\Helpers\InputNormalizer;
use Marwa\Framework\Validation\Helpers\MessageFormatter;
use Marwa\Framework\Validation\Helpers\TransformProcessor;
use Marwa\Framework\Validation\Helpers\TypeCoercer;
use Marwa\Framework\Validation\Helpers\TypeValidators;
use Marwa\Framework\Validation\Helpers\ValueAccessor;
use Marwa\Router\Contract\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class RequestValidator implements ValidatorInterface
{
    private RuleRegistry $registry;
    private InputNormalizer $normalizer;
    private ValueAccessor $accessor;
    private MessageFormatter $formatter;
    private TypeCoercer $coercer;
    private TypeValidators $typeValidators;
    private ComparisonValidators $comparisonValidators;
    private DateValidators $dateValidators;
    private TransformProcessor $transforms;

    public function __construct(
        ?RuleRegistry $registry = null,
        ?InputNormalizer $normalizer = null,
        ?ValueAccessor $accessor = null,
        ?MessageFormatter $formatter = null,
        ?TypeCoercer $coercer = null,
        ?TypeValidators $typeValidators = null,
        ?ComparisonValidators $comparisonValidators = null,
        ?DateValidators $dateValidators = null,
        ?TransformProcessor $transforms = null
    ) {
        $this->registry = $registry ?? $this->resolveDefaultRegistry();
        $this->normalizer = $normalizer ?? new InputNormalizer();
        $this->accessor = $accessor ?? new ValueAccessor();
        $this->formatter = $formatter ?? new MessageFormatter();
        $this->coercer = $coercer ?? new TypeCoercer();
        $this->typeValidators = $typeValidators ?? new TypeValidators();
        $this->comparisonValidators = $comparisonValidators ?? new ComparisonValidators($this->accessor);
        $this->dateValidators = $dateValidators ?? new DateValidators();
        $this->transforms = $transforms ?? new TransformProcessor();
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateInput(array $input, array $rules, array $messages = [], array $attributes = []): array
    {
        $validated = [];
        $errors = new ErrorBag();

        foreach ($this->normalizer->normalizeRules($rules) as $field => $fieldRules) {
            $normalizedRules = $this->normalizer->normalizeFieldRules($fieldRules);
            $exists = $this->accessor->hasValue($input, $field);
            $value = $this->accessor->getValue($input, $field, $exists);
            $value = $this->transforms->applyTransforms($value, $normalizedRules, $exists);

            $stopped = false;
            $failed = false;

            foreach ($normalizedRules as $rule) {
                if ($rule === 'bail') {
                    continue;
                }

                if ($rule === 'sometimes' && !$exists) {
                    continue 2;
                }

                if ($rule === 'nullable' && ($value === null || $value === '')) {
                    if ($exists) {
                        $this->accessor->setValue($validated, $field, null);
                    }

                    continue 2;
                }

                $error = $this->evaluateRule($field, $value, $exists, $rule, $input, $messages, $attributes);
                if ($error !== null) {
                    $errors->add($field, $error);
                    $failed = true;
                    $stopped = true;
                    break;
                }
            }

            if ($stopped) {
                continue;
            }

            if (!$failed && ($exists || $this->accessor->fieldHasDefault($normalizedRules))) {
                $value = $this->coercer->coerceValidatedValue($value, $normalizedRules);
                $this->accessor->setValue($validated, $field, $value);
            }
        }

        if ($errors->hasAny()) {
            throw new ValidationException($errors, $input);
        }

        return $validated;
    }

    public function validate(ServerRequestInterface $request, array $rules): array
    {
        return $this->validateRequest($request, $rules);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateRequest(
        ServerRequestInterface $request,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        return $this->validateInput(
            $this->normalizer->extractInput($request),
            $rules,
            $messages,
            $attributes
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @param array<string, string> $customRules
     * @return array<string, mixed>
     */
    public function validateInputWithCustomRules(
        array $input,
        array $rules,
        array $messages = [],
        array $attributes = [],
        array $customRules = []
    ): array {
        $registry = clone $this->registry;
        $registry->registerMany($customRules);

        $validator = new self(
            $registry,
            $this->normalizer,
            $this->accessor,
            $this->formatter,
            $this->coercer,
            $this->typeValidators,
            $this->comparisonValidators,
            $this->dateValidators,
            $this->transforms
        );

        return $validator->validateInput($input, $rules, $messages, $attributes);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @return array<string, string|array<int, mixed>>
     */
    public function normalize(array $rules): array
    {
        return $this->normalizer->normalizeRules($rules);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     */
    private function evaluateRule(
        string $field,
        mixed $value,
        bool $exists,
        mixed $rule,
        array $input,
        array $messages,
        array $attributes
    ): ?string {
        if ($rule instanceof EntityRuleInterface) {
            return $rule->validate($value, ['input' => $input, 'field' => $field]) ? null : $this->formatter->message(
                $field,
                'rule',
                'The :attribute field is invalid.',
                $messages,
                $attributes,
                ['value' => $value]
            );
        }

        if (!is_string($rule) && is_callable($rule)) {
            $result = $rule($value, $input, $field);

            if ($result === true || $result === null) {
                return null;
            }

            if (is_string($result) && $result !== '') {
                return $result;
            }

            return $this->formatter->message(
                $field,
                'rule',
                'The :attribute field is invalid.',
                $messages,
                $attributes,
                ['value' => $value]
            );
        }

        if (!is_string($rule) || $rule === '') {
            return null;
        }

        [$name, $parameterString] = array_pad(explode(':', $rule, 2), 2, '');
        $parameters = $parameterString === '' ? [] : array_map('trim', explode(',', $parameterString));

        return match ($name) {
            'required' => $this->typeValidators->isEmpty($value, true) ? $this->formatter->message($field, $name, 'The :attribute field is required.', $messages, $attributes, ['value' => $value]) : null,
            'present' => $exists ? null : $this->formatter->message($field, $name, 'The :attribute field must be present.', $messages, $attributes, ['value' => $value]),
            'filled' => $this->typeValidators->isEmpty($value, true) ? $this->formatter->message($field, $name, 'The :attribute field must not be empty.', $messages, $attributes, ['value' => $value]) : null,
            'string' => is_string($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be a string.', $messages, $attributes, ['value' => $value]),
            'integer' => $this->typeValidators->isInteger($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be an integer.', $messages, $attributes, ['value' => $value]),
            'numeric' => is_numeric($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be numeric.', $messages, $attributes, ['value' => $value]),
            'boolean' => $this->typeValidators->isBoolean($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be true or false.', $messages, $attributes, ['value' => $value]),
            'array' => is_array($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be an array.', $messages, $attributes, ['value' => $value]),
            'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false ? null : $this->formatter->message($field, $name, 'The :attribute field must be a valid email address.', $messages, $attributes, ['value' => $value]),
            'url' => filter_var((string) $value, FILTER_VALIDATE_URL) !== false ? null : $this->formatter->message($field, $name, 'The :attribute field must be a valid URL.', $messages, $attributes, ['value' => $value]),
            'accepted' => $this->typeValidators->isAccepted($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be accepted.', $messages, $attributes, ['value' => $value]),
            'declined' => $this->typeValidators->isDeclined($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be declined.', $messages, $attributes, ['value' => $value]),
            'file' => $value instanceof UploadedFileInterface ? null : $this->formatter->message($field, $name, 'The :attribute field must be a file upload.', $messages, $attributes, ['value' => $value]),
            'image' => $value instanceof UploadedFileInterface && $this->typeValidators->isImage($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be an image.', $messages, $attributes, ['value' => $value]),
            'confirmed' => $this->comparisonValidators->isConfirmed($field, $value, $input) ? null : $this->formatter->message($field, $name, 'The :attribute confirmation does not match.', $messages, $attributes, ['value' => $value]),
            'same' => $this->comparisonValidators->sameAs($field, $value, $input, $parameters[0] ?? '') ? null : $this->formatter->message($field, $name, 'The :attribute field must match :other.', $messages, $attributes, ['other' => $parameters[0] ?? '', 'value' => $value]),
            'in' => in_array((string) $value, $parameters, true) ? null : $this->formatter->message($field, $name, 'The :attribute field must be one of :values.', $messages, $attributes, ['values' => implode(', ', $parameters), 'value' => $value]),
            'min' => $this->comparisonValidators->compareMin($value, $parameters[0] ?? null) ? null : $this->formatter->message($field, $name, 'The :attribute field must be at least :min.', $messages, $attributes, ['min' => $parameters[0] ?? '', 'value' => $value]),
            'max' => $this->comparisonValidators->compareMax($value, $parameters[0] ?? null) ? null : $this->formatter->message($field, $name, 'The :attribute field must not be greater than :max.', $messages, $attributes, ['max' => $parameters[0] ?? '', 'value' => $value]),
            'between' => $this->comparisonValidators->compareBetween($value, $parameters[0] ?? null, $parameters[1] ?? null) ? null : $this->formatter->message($field, $name, 'The :attribute field must be between :min and :max.', $messages, $attributes, ['min' => $parameters[0] ?? '', 'max' => $parameters[1] ?? '', 'value' => $value]),
            'date' => $this->dateValidators->isDate($value) ? null : $this->formatter->message($field, $name, 'The :attribute field must be a valid date.', $messages, $attributes, ['value' => $value]),
            'date_format' => $this->dateValidators->matchesDateFormat($value, $parameters[0] ?? '') ? null : $this->formatter->message($field, $name, 'The :attribute field must match the format :format.', $messages, $attributes, ['format' => $parameters[0] ?? '', 'value' => $value]),
            'regex' => $this->dateValidators->matchesRegex($value, $parameters[0] ?? '') ? null : $this->formatter->message($field, $name, 'The :attribute field format is invalid.', $messages, $attributes, ['value' => $value]),
            'default' => null,
            'trim', 'lowercase', 'uppercase', 'nullable', 'sometimes', 'bail' => null,
            default => null,
        };
    }

    private function resolveDefaultRegistry(): RuleRegistry
    {
        try {
            return app(RuleRegistry::class);
        } catch (\Throwable) {
            return new RuleRegistry();
        }
    }
}
