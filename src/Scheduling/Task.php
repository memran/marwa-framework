<?php

declare(strict_types=1);

namespace Marwa\Framework\Scheduling;

use Marwa\Framework\Application;

final class Task
{
    /**
     * @var callable(Application, \DateTimeImmutable): mixed
     */
    private $callback;

    /**
     * @var callable(Application, \DateTimeImmutable): bool
     */
    private $filter;

    private int $intervalSeconds = 60;
    private bool $withoutOverlapping = false;
    private ?string $description = null;

    /**
     * @param callable(Application, \DateTimeImmutable): mixed $callback
     */
    public function __construct(
        private string $name,
        callable $callback
    ) {
        $this->callback = $callback;
        $this->filter = static fn (): bool => true;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(?string $description = null): string|self|null
    {
        if ($description === null) {
            return $this->description;
        }

        $this->description = $description;

        return $this;
    }

    public function everySecond(): self
    {
        return $this->everySeconds(1);
    }

    public function everySeconds(int $seconds): self
    {
        $this->intervalSeconds = max(1, $seconds);

        return $this;
    }

    public function everyMinute(): self
    {
        return $this->everySeconds(60);
    }

    public function hourly(): self
    {
        return $this->everySeconds(3600);
    }

    public function daily(): self
    {
        return $this->everySeconds(86400);
    }

    public function when(callable $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function withoutOverlapping(): self
    {
        $this->withoutOverlapping = true;

        return $this;
    }

    public function isDue(Application $app, \DateTimeImmutable $time): bool
    {
        $filter = $this->filter;

        if (!$filter($app, $time)) {
            return false;
        }

        if ($this->intervalSeconds <= 1) {
            return true;
        }

        return $time->getTimestamp() % $this->intervalSeconds === 0;
    }

    public function run(Application $app, \DateTimeImmutable $time): mixed
    {
        $callback = $this->callback;

        return $callback($app, $time);
    }

    public function shouldPreventOverlaps(): bool
    {
        return $this->withoutOverlapping;
    }

    public function intervalSeconds(): int
    {
        return $this->intervalSeconds;
    }
}
