<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Validation;

use Marwa\Router\Http\FormRequest as RouterFormRequest;
use Psr\Http\Message\ServerRequestInterface;

abstract class FormRequestAdapter extends RouterFormRequest
{
    private ?RequestValidatorAdapter $frameworkValidator;
    /**
     * @var array<string, mixed>|null
     */
    private ?array $validated = null;

    public function __construct(
        ServerRequestInterface $request,
        ?RequestValidatorAdapter $validator = null
    ) {
        parent::__construct($request, null);
        $this->frameworkValidator = $validator;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        if ($this->validated !== null) {
            return $this->validated;
        }

        if ($this->frameworkValidator === null) {
            $this->validated = $this->passedValidation($this->prepareForValidation($this->input()));

            return $this->validated;
        }

        $validated = $this->frameworkValidator->validateInputWithCustomRules(
            $this->prepareForValidation($this->input()),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        $this->validated = $this->passedValidation($validated);

        return $this->validated;
    }

    /**
     * @return array<string, mixed>
     */
    public function safe(): array
    {
        return $this->validated();
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    protected function prepareForValidation(array $input): array
    {
        return $input;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function passedValidation(array $validated): array
    {
        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        $input = array_merge(
            $this->request()->getQueryParams(),
            is_array($this->request()->getParsedBody()) ? $this->request()->getParsedBody() : []
        );

        return $input;
    }
}
