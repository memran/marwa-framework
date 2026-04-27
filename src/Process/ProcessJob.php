<?php

declare(strict_types=1);

namespace Marwa\Framework\Process;

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

        if ($command === '') {
            throw new \InvalidArgumentException('Process command is required');
        }

        $adapter = \app(\Marwa\Framework\Adapters\Process\ProcessAdapter::class);

        $result = $adapter->execute($command, $options);

        $logger = \app(\Psr\Log\LoggerInterface::class);

        if ($logger) {
            $logger->info('Process executed', [
                'command' => $command,
                'exit_code' => $result->getExitCode(),
                'duration' => $result->getDuration(),
            ]);
        }
    }
}
