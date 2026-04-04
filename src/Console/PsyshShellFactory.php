<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use Marwa\Framework\Application;
use Marwa\Framework\Contracts\ShellFactoryInterface;

final class PsyshShellFactory implements ShellFactoryInterface
{
    public function available(): bool
    {
        return class_exists('Psy\\Shell');
    }

    public function run(Application $app, array $variables = []): int
    {
        $shellClass = 'Psy\\Shell';

        if (!class_exists($shellClass)) {
            return 1;
        }

        if (!is_callable([$shellClass, 'create'])) {
            throw new \RuntimeException('Installed PsySH shell does not expose a create() factory.');
        }

        $create = \Closure::fromCallable([$shellClass, 'create']);
        $shell = $create();

        if (method_exists($shell, 'setScopeVariables')) {
            $shell->setScopeVariables($variables);
        }

        if (method_exists($shell, 'run')) {
            $shell->run();

            return 0;
        }

        if (method_exists($shell, 'start')) {
            $shell->start();

            return 0;
        }

        throw new \RuntimeException('Installed PsySH shell does not expose a runnable entry point.');
    }
}
