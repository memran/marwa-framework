<?php

declare(strict_types=1);

namespace Marwa\Framework\Process;

use Marwa\Framework\Queue\QueuedJob;

class ProcessJob extends QueuedJob
{
    public const NAME = 'process';

    public function handle(array $payload): void
    {
        $command = $payload['command'] ?? '';
        $options = $payload['options'] ?? [];
        
        if (empty($command)) {
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