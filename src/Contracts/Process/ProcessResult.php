<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\Process;

final class ProcessResult
{
    private int $exitCode;
    private string $output;
    private string $error;
    private float $duration;
    private int $memory;
    private \DateTimeInterface $startTime;
    private \DateTimeInterface $endTime;
    private string $command;

    public function __construct(
        string $command,
        int $exitCode,
        string $output,
        string $error,
        float $duration,
        int $memory,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime
    ) {
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->error = $error;
        $this->duration = $duration;
        $this->memory = $memory;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public static function fromSymfonyProcess(\Symfony\Component\Process\Process $process, float $startTime): self
    {
        $endTime = microtime(true);
        
        return new self(
            $process->getCommandLine(),
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
            $endTime - $startTime,
            memory_get_peak_usage(true),
            \DateTimeImmutable::createFromFormat('U', (string) $startTime),
            \DateTimeImmutable::createFromFormat('U', (string) $endTime)
        );
    }

    public static function error(string $command, string $error, float $duration = 0): self
    {
        return new self(
            $command,
            -1,
            '',
            $error,
            $duration,
            memory_get_peak_usage(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getMemory(): int
    {
        return $this->memory;
    }

    public function isSuccessful(): bool
    {
        return $this->exitCode === 0;
    }

    public function getLines(): array
    {
        return array_filter(explode("\n", $this->output));
    }

    public function getLastLines(int $count = 10): array
    {
        $lines = $this->getLines();
        return array_slice($lines, -$count);
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'exit_code' => $this->exitCode,
            'output' => $this->output,
            'error' => $this->error,
            'duration' => $this->duration,
            'memory' => $this->memory,
            'start_time' => $this->startTime->format('Y-m-d H:i:s.u'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s.u'),
            'successful' => $this->isSuccessful(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}