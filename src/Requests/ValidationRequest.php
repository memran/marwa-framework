<?php

namespace Marwa\App\Requests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Marwa\App\Requests\Request;
use Marwa\App\Exceptions\ValidationException;


class ValidateRequest
{
    /**
     * Validation rules for specific routes.
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Custom validation messages.
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Create a new validation middleware instance.
     *
     * @param array $rules
     * @param array $messages
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Handle the request and validate it.
     *
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        // Convert to custom Request if not already
        $customRequest = $request instanceof Request
            ? $request
            : new Request();

        // Determine rules based on route
        $path = $customRequest->path();
        $method = $customRequest->method();
        $rules = $this->getRulesForRoute($path, $method);

        if (!empty($rules)) {
            try {
                $customRequest->validate($rules, $this->messages);
            } catch (ValidationException $e) {
                return new JsonResponse([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        }

        return $next($customRequest);
    }

    /**
     * Get validation rules for the current route.
     *
     * @param string $path
     * @param string $method
     * @return array
     */
    protected function getRulesForRoute(string $path, string $method): array
    {
        $key = "{$method}:{$path}";
        return $this->rules[$key] ?? [];
    }

    /**
     * Add validation rules for a specific route.
     *
     * @param string $method
     * @param string $path
     * @param array $rules
     * @return $this
     */
    public function addRules(string $method, string $path, array $rules): self
    {
        $this->rules["{$method}:{$path}"] = $rules;
        return $this;
    }

    /**
     * Add custom validation messages.
     *
     * @param array $messages
     * @return $this
     */
    public function addMessages(array $messages): self
    {
        $this->messages = array_merge($this->messages, $messages);
        return $this;
    }
}
