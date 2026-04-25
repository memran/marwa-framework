<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts\Process;

interface ProcessOutputHandlerInterface
{
    public function write(ProcessResult $result): void;

    public function configuration(): array;
}