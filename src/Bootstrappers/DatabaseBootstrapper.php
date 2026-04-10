<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use Marwa\DB\Bootstrap;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Facades\DB;
use Marwa\DB\ORM\Model;
use Marwa\DB\Schema\Schema;
use Marwa\DB\Seeder\SeedRunner;
use Marwa\Framework\Application;
use Marwa\Framework\Config\DatabaseConfig;
use Marwa\Framework\Database\DBForge;
use Marwa\Framework\Supports\Config;
use Psr\Log\LoggerInterface;

final class DatabaseBootstrapper
{
    private bool $booted = false;

    /**
     * @var array{
     *     enabled: bool,
     *     default: string,
     *     connections: array<string, array<string, mixed>>,
     *     debug: bool,
     *     useDebugPanel: bool,
     *     migrationsPath: string,
     *     seedersPath: string,
     *     seedersNamespace: string
     * }|null
     */
    private ?array $databaseConfig = null;

    private ?ConnectionManager $manager = null;

    public function __construct(
        private Application $app,
        private Container $container,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    public function bootstrap(): ?ConnectionManager
    {
        if ($this->booted || !class_exists(Bootstrap::class)) {
            $this->booted = true;

            return $this->manager;
        }

        $config = $this->databaseConfig();

        if (!$config['enabled']) {
            $this->booted = true;

            return null;
        }

        $manager = Bootstrap::init(
            DatabaseConfig::toPackageConfig($config),
            $this->logger,
            $config['useDebugPanel']
        );

        DB::setManager($manager);
        Model::setConnectionManager($manager, $config['default']);
        Schema::init($manager, $config['default']);

        $this->container->addShared(ConnectionManager::class, $manager);
        $this->container->addShared(\Marwa\DB\Connection\ConnectionInterface::class, $manager);

        // Lazy registration - only instantiated when first requested
        $this->container->addShared(SeedRunner::class, fn () => new SeedRunner(
            cm: $manager,
            logger: $this->logger,
            connection: $config['default'],
            seedPath: $config['seedersPath'],
            seedNamespace: $config['seedersNamespace'],
        ));
        $this->container->addShared(DBForge::class, fn () => DBForge::create($manager, $config['default']));
        $this->app->set('db', $manager);
        $this->manager = $manager;
        $this->booted = true;

        return $this->manager;
    }

    public function manager(): ?ConnectionManager
    {
        if (!$this->booted) {
            return $this->bootstrap();
        }

        return $this->manager;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     default: string,
     *     connections: array<string, array<string, mixed>>,
     *     debug: bool,
     *     useDebugPanel: bool,
     *     migrationsPath: string,
     *     seedersPath: string,
     *     seedersNamespace: string
     * }
     */
    public function databaseConfig(): array
    {
        if ($this->databaseConfig !== null) {
            return $this->databaseConfig;
        }

        $this->config->loadIfExists(DatabaseConfig::KEY . '.php');
        $this->databaseConfig = DatabaseConfig::merge($this->app, $this->config->getArray(DatabaseConfig::KEY, []));

        return $this->databaseConfig;
    }
}
