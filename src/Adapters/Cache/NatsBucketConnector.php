<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

final class NatsBucketConnector
{
    /**
     * @param array{
     *     bucket: string,
     *     host: string,
     *     port: int,
     *     servers: list<string>,
     *     user: string|null,
     *     pass: string|null,
     *     token: string|null,
     *     jwt: string|null,
     *     nkey: string|null,
     *     credentials: string|null,
     *     tlsCaFile: string|null,
     *     tlsCertFile: string|null,
     *     tlsKeyFile: string|null,
     *     timeout: int
     * } $config
     */
    public function connect(array $config): NatsBucketInterface
    {
        $configurationClass = 'Basis\\Nats\\Configuration';
        $clientClass = 'Basis\\Nats\\Client';

        if (!class_exists($configurationClass) || !class_exists($clientClass)) {
            throw new \RuntimeException('NATS cache requires basis-company/nats. Install it with: composer require basis-company/nats');
        }

        $options = $this->connectionOptions($config);

        /** @var object $configuration */
        $configuration = new $configurationClass(...$options);

        /** @var object $client */
        $client = new $clientClass($configuration);

        if (!method_exists($client, 'getApi')) {
            throw new \RuntimeException('The configured NATS client does not expose a JetStream API.');
        }

        $api = $client->getApi();

        if (!is_object($api) || !method_exists($api, 'getBucket')) {
            throw new \RuntimeException('The configured NATS client does not support JetStream Key-Value buckets.');
        }

        $bucket = $api->getBucket($config['bucket']);

        if (!is_object($bucket)) {
            throw new \RuntimeException(sprintf('Unable to resolve NATS cache bucket [%s].', $config['bucket']));
        }

        return new NatsBucket($bucket);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function connectionOptions(array $config): array
    {
        $options = [];

        foreach (['host', 'port', 'user', 'pass', 'token', 'jwt', 'nkey', 'tlsCaFile', 'tlsCertFile', 'tlsKeyFile', 'timeout'] as $key) {
            if (($config[$key] ?? null) !== null && $config[$key] !== '' && $config[$key] !== []) {
                $options[$key] = $config[$key];
            }
        }

        $options = array_replace($options, $this->serverOptions($config['servers'] ?? []));

        $credentials = $config['credentials'] ?? null;

        if (is_string($credentials) && $credentials !== '') {
            $options = array_replace($this->credentialsOptions($credentials), $options);
        }

        return $options;
    }

    /**
     * @param mixed $servers
     * @return array{host?: string, port?: int}
     */
    private function serverOptions(mixed $servers): array
    {
        if (!is_array($servers)) {
            return [];
        }

        $server = $this->firstServer($servers);

        if ($server === null) {
            return [];
        }

        $parts = parse_url(str_contains($server, '://') ? $server : 'nats://' . $server);

        if (!is_array($parts) || !is_string($parts['host'] ?? null) || $parts['host'] === '') {
            return [];
        }

        $options = ['host' => $parts['host']];

        if (is_int($parts['port'] ?? null)) {
            $options['port'] = $parts['port'];
        }

        return $options;
    }

    /**
     * @param array<mixed> $servers
     */
    private function firstServer(array $servers): ?string
    {
        foreach ($servers as $server) {
            if (is_string($server) && trim($server) !== '') {
                return trim($server);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function credentialsOptions(string $path): array
    {
        $parserClass = $this->credentialsParserClass();

        if (!class_exists($parserClass)) {
            return [];
        }

        try {
            $options = call_user_func([$parserClass, 'fromFile'], $path);
        } catch (\Throwable) {
            return [];
        }

        return is_array($options) ? $options : [];
    }

    private function credentialsParserClass(): string
    {
        return 'Basis\\Nats\\NKeys\\CredentialsParser';
    }
}
