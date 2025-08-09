<?php

namespace Marwa\App\Requests;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Rakit\Validation\Validator;
use Marwa\App\Exceptions\ValidationException;


final class Request extends ServerRequest implements ServerRequestInterface
{
    /**
     * The decoded input data from the request.
     *
     * @var array|null
     */
    protected ?array $input = null;

    /**
     * The request URL.
     *
     * @var string
     */
    protected string $url = '/';

    /**
     * Rakit Validator instance.
     *
     * @var Validator
     */
    protected Validator $validator;

    protected $validation;

    /**
     * Constructor to initialize the request with trimmed URL and validator.
     */
    public function __construct()
    {
        // Initialize Rakit Validator
        $this->validator = new Validator();

        // Assign global request URI
        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $this->url = $_SERVER['REQUEST_URI'] ?: '/';
            $this->trimBase();
        }

        // Initialize ServerRequest using ServerRequestFactory
        $request = ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );

        // Update the request URI with the trimmed URL
        $uri = $request->getUri()->withPath($this->url);
        parent::__construct(
            $request->getServerParams(),
            $request->getUploadedFiles(),
            $uri,
            $request->getMethod(),
            $request->getBody(),
            $request->getHeaders(),
            $request->getCookieParams(),
            $request->getQueryParams(),
            $request->getParsedBody(),
            $request->getProtocolVersion(),
            $request->getAttributes()
        );
    }

    /**
     * Create a new request instance from server parameters.
     *
     * @param array $serverParams Server parameters, typically $_SERVER
     * @param array $uploadedFiles Uploaded files, typically $_FILES
     * @param string|null $uri URI for the request, if any
     * @param string|null $method HTTP method for the request, if any
     * @param mixed $body Request body
     * @param array $headers Request headers
     * @return static
     */
    public static function createFromServer(
        array $serverParams = [],
        array $uploadedFiles = [],
        ?string $uri = null,
        ?string $method = null,
        $body = 'php://input',
        array $headers = []
    ): self {
        $request = new static();
        if ($uri !== null) {
            $request->url = $uri;
            $request->trimBase();
        }
        return $request->withUri($request->getUri()->withPath($request->url));
    }

    /**
     * Trim the base path from the request URL.
     */
    protected function trimBase(): void
    {
        // Get the application base path
        $base = parse_url(base_url(), PHP_URL_PATH) ?: '/';
        // Remove the base path from the URL
        $route = substr($this->url, strlen($base));
        $this->url = sprintf('/%s', trim($route, '/'));
        if ($this->url === '') {
            $this->url = '/';
        }
    }

    /**
     * Get the request URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the request URL.
     *
     * @param string $url
     */
    protected function setRequestUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get all input data from the request (query, body, and route parameters).
     *
     * @return array
     */
    public function all(): array
    {
        return $this->getInput();
    }

    /**
     * Get an input item from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        $input = $this->getInput();

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    /**
     * Get all input data from the request.
     *
     * @return array
     */
    public function getInput(): array
    {
        if ($this->input === null) {
            $this->input = array_merge(
                $this->getQueryParams(),
                $this->getParsedBody() ?: [],
                $this->getAttributes()
            );
        }

        return $this->input;
    }

    /**
     * Determine if the request contains a given input item key.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->getInput());
    }

    /**
     * Get all query parameters.
     *
     * @return array
     */
    public function query(): array
    {
        return $this->getQueryParams();
    }

    /**
     * Get a specific query parameter.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getQuery(?string $key = null, $default = null)
    {
        $query = $this->getQueryParams();

        if ($key === null) {
            return $query;
        }

        return $query[$key] ?? $default;
    }

    /**
     * Get all request body parameters.
     *
     * @return array
     */
    public function post(): array
    {
        return $this->getParsedBody() ?: [];
    }

    /**
     * Get a specific body parameter.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost(?string $key = null, $default = null)
    {
        $post = $this->getParsedBody() ?: [];

        if ($key === null) {
            return $post;
        }

        return $post[$key] ?? $default;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return strtoupper($this->getMethod());
    }

    /**
     * Determine if the request is of the given method.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    /**
     * Get the URL path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->getUri()->withPath($this->url)->getPath();
    }

    /**
     * Get a specific header value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function header(string $name, $default = null)
    {
        return $this->getHeader($name) ?: $default;
    }

    /**
     * Get all headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->getHeaders();
    }

    /**
     * Get the client's IP address.
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        $serverParams = $this->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get all uploaded files.
     *
     * @return array
     */
    public function files(): array
    {
        return $this->getUploadedFiles();
    }

    /**
     * Get a specific uploaded file.
     *
     * @param string|null $key
     * @param mixed $default
     * @return UploadedFileInterface|array|null
     */
    public function file(?string $key = null, $default = null)
    {
        $files = $this->getUploadedFiles();

        if ($key === null) {
            return $files;
        }

        return $files[$key] ?? $default;
    }

    /**
     * Validate the request input and files against the provided rules.
     *
     * @param array $rules
     * @param array $messages Custom error messages
     * @return array Validated input
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = []): array
    {
        $inputs = $this->all();
        $files = $this->files();
        $this->validation = $this->validator->validate(array_merge($inputs, $files), $rules, $messages);

        if ($this->validation->fails()) {
            throw new ValidationException($this->validation->errors()->all());
        }

        return array_intersect_key(array_merge($inputs, $files), array_flip(array_keys($rules)));
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->validation->errors()->all();
    }

    /**
     * Get the route parameters.
     *
     * @return array
     */
    public function route(): array
    {
        return $this->getAttributes();
    }

    /**
     * Get a specific route parameter.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function routeParam(string $key, $default = null)
    {
        return $this->getAttribute($key, $default);
    }
}
