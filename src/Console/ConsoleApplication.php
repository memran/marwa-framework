<?php

declare(strict_types=1);

namespace Marwa\Framework\Console;

use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Command\Command;

class ConsoleApplication extends SymfonyConsole
{
    /**
     * @param list<Command> $commands
     */
    public function bootstrap(string $name, string $version, array $commands): static
    {
        $this->setName($name);
        $this->setVersion($version);

        foreach ($commands as $command) {
            $this->add($command);
        }

        return $this;
    }
}
