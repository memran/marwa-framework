<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use Marwa\Framework\Adapters\Cache\ScrapbookCacheAdapter;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Console\CommandDiscovery;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\ConsoleApplication;
use Marwa\Framework\Console\ConsoleKernel;
use Marwa\Framework\Console\PsyshShellFactory;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Contracts\SecurityInterface;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Contracts\ShellFactoryInterface;
use Marwa\Framework\Notifications\Channels\KafkaChannel;
use Marwa\Framework\Notifications\NotificationManager;
use Marwa\Framework\Queue\FileQueue;
use Marwa\Framework\Scheduling\Scheduler;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreResolver;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Security\Security;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\EncryptedSession;
use Marwa\Framework\Supports\Http;
use Marwa\Framework\Supports\Mailer;
use Marwa\Framework\Supports\Storage;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Framework\Views\View as FrameworkView;
use Marwa\Router\Contract\ValidatorInterface as RouterValidatorInterface;
use Psr\Log\LoggerInterface;

final class CoreBindingsBootstrapper
{
    public function bootstrap(Application $app, Container $container): void
    {
        date_default_timezone_set((string) env('TIMEZONE', 'Asia/Dhaka'));

        $container->addShared(Application::class, $app);
        $container->addShared(Container::class, $container);

        $container->addShared(Config::class)
            ->addArgument($app->basePath('config'));

        $container->addShared(Storage::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class));

        $container->addShared(LoggerAdapter::class, function () use ($app, $container) {
            return (new LoggerAdapter($app, $container->get(Config::class)))->getLogger();
        });

        $container->addShared(LoggerInterface::class, function () use ($container) {
            return $container->get(LoggerAdapter::class);
        });

        $container->addShared(EventDispatcherAdapter::class)
            ->addArgument($container)
            ->addArgument($container->get(Config::class));

        $container->addShared(EventDispatcherInterface::class, function () use ($container) {
            return $container->get(EventDispatcherAdapter::class);
        });

        $container->addShared(ScrapbookCacheAdapter::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class));

        $container->addShared(CacheInterface::class, function () use ($container) {
            return $container->get(ScrapbookCacheAdapter::class);
        });

        $container->addShared(Http::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class));

        $container->addShared(HttpClientInterface::class, function () use ($container) {
            return $container->get(Http::class);
        });

        $container->addShared(Mailer::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class));

        $container->addShared(MailerInterface::class, function () use ($container) {
            return $container->get(Mailer::class);
        });

        $container->addShared(FrameworkView::class, function () use ($container) {
            return new FrameworkView($container->get(\Marwa\Framework\Adapters\ViewAdapter::class));
        });

        $container->addShared(NotificationManager::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class));

        $container->addShared(KafkaChannel::class)
            ->addArgument($app);

        $container->addShared(EncryptedSession::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class));

        $container->addShared(SessionInterface::class, function () use ($container) {
            return $container->get(EncryptedSession::class);
        });

        $container->addShared(RequestValidator::class);

        $container->addShared(RouterValidatorInterface::class, function () use ($container) {
            return $container->get(RequestValidator::class);
        });

        $container->addShared(Security::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(CacheInterface::class))
            ->addArgument($container->get(SessionInterface::class));

        $container->addShared(SecurityInterface::class, function () use ($container) {
            return $container->get(Security::class);
        });

        $container->addShared(RiskAnalyzer::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(ErrorHandlerAdapter::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(ErrorHandlerBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container->get(ErrorHandlerAdapter::class));

        $container->addShared(DatabaseBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(AppBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(ProviderBootstrapper::class))
            ->addArgument($container->get(ErrorHandlerBootstrapper::class))
            ->addArgument($container->get(DatabaseBootstrapper::class))
            ->addArgument($container->get(ModuleBootstrapper::class));

        $container->addShared(ModuleBootstrapper::class)
            ->addArgument($app)
            ->addArgument($container)
            ->addArgument($container->get(Config::class));

        $container->addShared(CommandRegistry::class)
            ->addArgument($app)
            ->addArgument($container)
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(PsyshShellFactory::class);

        $container->addShared(ShellFactoryInterface::class, function () use ($container) {
            return $container->get(PsyshShellFactory::class);
        });

        $container->addShared(CommandDiscovery::class)
            ->addArgument($app)
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(FileQueue::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class));

        $container->addShared(ScheduleStoreResolver::class)
            ->addArgument($container->get(DatabaseBootstrapper::class))
            ->addArgument($container->get(CacheInterface::class));

        $container->addShared(Scheduler::class)
            ->addArgument($app)
            ->addArgument($container->get(LoggerInterface::class))
            ->addArgument($container->get(FileQueue::class))
            ->addArgument($container->get(ScheduleStoreResolver::class));

        $container->addShared(ConsoleApplication::class)
            ->addArgument(ConsoleConfig::defaults($app)['name'])
            ->addArgument(ConsoleConfig::defaults($app)['version']);

        $container->addShared(ConsoleKernel::class)
            ->addArgument($app)
            ->addArgument($container->get(Config::class))
            ->addArgument($container->get(LoggerInterface::class))
            ->addArgument($container->get(AppBootstrapper::class))
            ->addArgument($container->get(CommandRegistry::class))
            ->addArgument($container->get(CommandDiscovery::class))
            ->addArgument($container->get(ConsoleApplication::class));
    }
}
