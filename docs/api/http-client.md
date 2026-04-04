# HTTP Client API

## `Marwa\Framework\Supports\Http`

### `configuration(): array`

Returns the merged HTTP client configuration.

### `client(?string $name = null): \GuzzleHttp\ClientInterface`

Returns a configured Guzzle client instance for the selected profile.

### `withClient(string $name): self`

Returns a cloned client wrapper scoped to a named client profile.

### `withOptions(array $options): self`

Returns a cloned client wrapper with additional request options.

### `get(string $uri = '', array $options = []): ResponseInterface`
### `post(string $uri = '', array $options = []): ResponseInterface`
### `put(string $uri = '', array $options = []): ResponseInterface`
### `patch(string $uri = '', array $options = []): ResponseInterface`
### `delete(string $uri = '', array $options = []): ResponseInterface`
### `head(string $uri = '', array $options = []): ResponseInterface`
### `options(string $uri = '', array $options = []): ResponseInterface`

Convenience verbs for `request()`.

### `json(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface`

Sends a JSON request with a `json` payload option.

### `form(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface`

Sends a form-encoded request with `form_params`.

### `multipart(string $method, string $uri = '', array $parts = [], array $options = []): ResponseInterface`

Sends a multipart request with file or part payloads.
