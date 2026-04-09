<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Adapters\RouterAdapter;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'route:cache', description: 'Compile routes into the bootstrap cache directory.')]
final class RouteCacheCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->container()->get(AppBootstrapper::class)->bootstrap();
        $router = $this->container()->get(RouterAdapter::class);
        $file = BootstrapConfig::defaults($this->app())['routeCache'];

        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        $router->compileRoutesTo($file);
        $output->writeln(sprintf('<info>Route cache created:</info> %s', $file));

        return Command::SUCCESS;
    }
}
