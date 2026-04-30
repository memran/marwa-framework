<?php

declare(strict_types=1);

namespace Marwa\Framework\Process;

use Marwa\Framework\Adapters\Process\ProcessAdapter;
use Marwa\Framework\Application;

final class ProcessJob
{
    public const NAME = 'process';

    /**
     * @var array<string, mixed>
     */
    private array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    public function handle(Application $app): void
    {
        $command = (string) ($this->payload['command'] ?? '');
        $options = is_array($this->payload['options'] ?? null) ? $this->payload['options'] : [];
        $input = $this->payload['input'] ?? null;
        $outputHandler = is_array($this->payload['output_handler'] ?? null) ? $this->payload['output_handler'] : null;

        if ($command === '') {
            throw new \InvalidArgumentException('Process command is required');
        }

        /** @var ProcessAdapter $adapter */
        $adapter = $app->make(ProcessAdapter::class);

        if (is_string($input) && $input !== '') {
            $adapter->input($input);
        }

        $retryAttempts = (int) ($options['retry'] ?? 0);
        $retryDelayMs = (int) ($options['retry_delay_ms'] ?? 1000);
        if ($retryAttempts > 0) {
            $adapter->retry($retryAttempts, max(1, $retryDelayMs));
        }

        if (is_array($outputHandler)) {
            $this->restoreOutputHandler($adapter, $outputHandler);
        }

        $result = $adapter->execute($command, $options);

        $logger = $app->has(\Psr\Log\LoggerInterface::class) ? $app->make(\Psr\Log\LoggerInterface::class) : null;

        if ($logger) {
            $logger->info('Process executed', [
                'command' => $command,
                'exit_code' => $result->getExitCode(),
                'duration' => $result->getDuration(),
            ]);
        }
    }

    /**
     * @param array{type?: string, config?: array<string, mixed>} $outputHandler
     */
    private function restoreOutputHandler(ProcessAdapter $adapter, array $outputHandler): void
    {
        $type = (string) ($outputHandler['type'] ?? '');
        $config = is_array($outputHandler['config'] ?? null) ? $outputHandler['config'] : [];

        match ($type) {
            'file' => $adapter->toFile((string) ($config['path'] ?? '')),
            'db' => $adapter->toDb((string) ($config['table'] ?? ''), is_array($config['metadata'] ?? null) ? $config['metadata'] : []),
            'redis' => $adapter->toRedis((string) ($config['key'] ?? ''), (int) ($config['ttl'] ?? 86400)),
            default => null,
        };
    }
}
