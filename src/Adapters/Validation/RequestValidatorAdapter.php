<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Validation;

use Marwa\Router\Contract\ValidatorInterface;
use Marwa\Support\Validation\Contracts\RuleInterface;
use Marwa\Support\Validation\RequestValidator as SupportRequestValidator;
use Marwa\Support\Validation\RuleRegistry as SupportRuleRegistry;
use Psr\Http\Message\ServerRequestInterface;

final class RequestValidatorAdapter implements ValidatorInterface
{
    public function __construct(
        private SupportRequestValidator $validator,
        private SupportRuleRegistry $registry
    ) {}

    public function validate(ServerRequestInterface $request, array $rules): array
    {
        return $this->validator->validateRequest($request, $rules);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string|array<int, mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    public function validateInput(
        array $input,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        return $this->validator->validateInput($input, $rules, $messages, $attributes);
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
        return $this->validator->validateRequest($request, $rules, $messages, $attributes);
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
        if ($customRules === []) {
            return $this->validator->validateInput($input, $rules, $messages, $attributes);
        }

        $registry = clone $this->registry;
        $registry->registerMany($customRules);

        $normalizedRules = $this->validator->normalize($rules);
        $preparedRules = [];

        foreach ($normalizedRules as $field => $fieldRules) {
            $preparedRules[$field] = $this->prepareFieldRules((array) $fieldRules, $registry);
        }

        $preparedValidator = new SupportRequestValidator($registry);

        return $preparedValidator->validateInput($input, $preparedRules, $messages, $attributes);
    }

    /**
     * @param array<string, string|array<int, mixed>> $rules
     * @return array<string, string|array<int, mixed>>
     */
    public function normalize(array $rules): array
    {
        return $this->validator->normalize($rules);
    }

    /**
     * @param array<int, mixed> $rules
     * @return array<int, mixed>
     */
    private function prepareFieldRules(array $rules, SupportRuleRegistry $registry): array
    {
        $prepared = [];
        $passthrough = [
            'trim',
            'lowercase',
            'uppercase',
            'default',
            'nullable',
            'sometimes',
            'bail',
        ];

        foreach ($rules as $rule) {
            if (!is_string($rule) || $rule === '') {
                $prepared[] = $rule;
                continue;
            }

            [$name, $params] = array_pad(explode(':', $rule, 2), 2, '');

            if (in_array($name, $passthrough, true)) {
                $prepared[] = $rule;
                continue;
            }

            $resolved = $registry->resolve($name, $params);

            if ($resolved instanceof RuleInterface) {
                $prepared[] = $resolved;
                continue;
            }

            $prepared[] = $rule;
        }

        return $prepared;
    }
}
