<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Marwa\Framework\Application;
use Marwa\Framework\Config\HttpConfig;
use Marwa\Framework\Contracts\HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Http implements HttpClientInterface
{
    /**
     * @var array{
     *     enabled: bool,
     *     default: string,
     *     clients: array<string, array<string, mixed>>
     * }
     */
    private array $settings;

    /**
     * @var array<string, ClientInterface>
     */
    private array $clients = [];

    /**
     * @var array<string, mixed>
     */
    private array $pendingOptions = [];

    private ?string $activeClient = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {
        $this->config->loadIfExists(HttpConfig::KEY . '.php');
        $this->settings = HttpConfig::merge($this->app, $this->config->getArray(HttpConfig::KEY, []));
    }

    public function configuration(): array
    {
        return $this->settings;
    }

    public function client(?string $name = null): ClientInterface
    {
        if (!$this->settings['enabled']) {
            throw new \RuntimeException('HTTP client is disabled.');
        }

        $clientName = $name ?? $this->activeClient ?? $this->settings['default'];

        if (isset($this->clients[$clientName])) {
            return $this->clients[$clientName];
        }

        if (!isset($this->settings['clients'][$clientName])) {
            throw new \InvalidArgumentException(sprintf('HTTP client profile [%s] is not configured.', $clientName));
        }

        $options = $this->pruneNulls($this->settings['clients'][$clientName]);

        return $this->clients[$clientName] = new Client($options);
    }

    public function withClient(string $name): self
    {
        $clone = clone $this;
        $clone->activeClient = $name;

        return $clone;
    }

    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->pendingOptions = array_replace_recursive($clone->pendingOptions, $options);

        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        return $this->withOptions(['headers' => $headers]);
    }

    public function header(string $name, string $value): self
    {
        return $this->withHeaders([$name => $value]);
    }

    public function token(string $token, string $type = 'Bearer'): self
    {
        return $this->header('Authorization', trim($type . ' ' . $token));
    }

    public function baseUri(string $uri): self
    {
        return $this->withOptions(['base_uri' => $uri]);
    }

    public function timeout(int|float $seconds): self
    {
        return $this->withOptions(['timeout' => $seconds]);
    }

    public function connectTimeout(int|float $seconds): self
    {
        return $this->withOptions(['connect_timeout' => $seconds]);
    }

    public function verify(bool|string $verify): self
    {
        return $this->withOptions(['verify' => $verify]);
    }

    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        return $this->client()->request($method, $uri, $this->mergeOptions($options));
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->client()->send($request, $this->mergeOptions($options));
    }

    public function get(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    public function post(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    public function put(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    public function patch(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    public function delete(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    public function head(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    public function options(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request('OPTIONS', $uri, $options);
    }

    public function json(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface
    {
        $options['json'] = $payload;

        return $this->request($method, $uri, $options);
    }

    public function form(string $method, string $uri = '', array $payload = [], array $options = []): ResponseInterface
    {
        $options['form_params'] = $payload;

        return $this->request($method, $uri, $options);
    }

    public function multipart(string $method, string $uri = '', array $parts = [], array $options = []): ResponseInterface
    {
        $options['multipart'] = $parts;

        return $this->request($method, $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function mergeOptions(array $options): array
    {
        return array_replace_recursive($this->pendingOptions, $this->pruneNulls($options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function pruneNulls(array $options): array
    {
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $options[$key] = $this->pruneNulls($value);
                continue;
            }

            if ($value === null) {
                unset($options[$key]);
            }
        }

        return $options;
    }
}
