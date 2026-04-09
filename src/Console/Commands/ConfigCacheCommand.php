<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config:cache', description: 'Build the merged config cache file.')]
final class ConfigCacheCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configDir = $this->basePath('config');
        $cacheFile = BootstrapConfig::defaults($this->app())['configCache'];

        $payload = [];

        foreach (glob($configDir . '/*.php') ?: [] as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $value = require $file;

            if (!is_array($value)) {
                throw new \UnexpectedValueException(sprintf('Config file [%s] must return an array.', $file));
            }

            $payload[$key] = $value;
        }

        $this->writePhpArrayCache($cacheFile, $payload);
        $output->writeln(sprintf('<info>Config cache created:</info> %s', $cacheFile));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writePhpArrayCache(string $file, array $payload): void
    {
        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        file_put_contents($file, $contents, LOCK_EX);
    }
}
