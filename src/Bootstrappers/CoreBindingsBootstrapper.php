<?php

declare(strict_types=1);

namespace Marwa\Framework\Bootstrappers;

use League\Container\Container;
use Marwa\Framework\Adapters\Cache\ScrapbookCacheAdapter;
use Marwa\Framework\Adapters\ErrorHandlerAdapter;
use Marwa\Framework\Adapters\Event\EventDispatcherAdapter;
use Marwa\Framework\Adapters\Logger\LoggerAdapter;
use Marwa\Framework\Adapters\Validation\RequestValidatorAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Authorization\AuthManager;
use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Authorization\Gate;
use Marwa\Framework\Authorization\PolicyRegistry;
use Marwa\Framework\Config\ConsoleConfig;
use Marwa\Framework\Config\LoggerConfig;
use Marwa\Framework\Console\CommandDiscovery;
use Marwa\Framework\Console\CommandRegistry;
use Marwa\Framework\Console\ConsoleApplication;
use Marwa\Framework\Console\ConsoleKernel;
use Marwa\Framework\Console\PsyshShellFactory;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Contracts\HttpClientInterface;
use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Contracts\QueueInterface;
use Marwa\Framework\Contracts\ScheduleStoreResolverInterface;
use Marwa\Framework\Contracts\SecurityInterface;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Contracts\ShellFactoryInterface;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Notifications\Channels\KafkaChannel;
use Marwa\Framework\Notifications\NotificationManager;
use Marwa\Framework\Queue\QueueManager;
use Marwa\Framework\Scheduling\Scheduler;
use Marwa\Framework\Scheduling\Stores\ScheduleStoreResolver;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Security\Security;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\EncryptedSession;
use Marwa\Framework\Supports\Http;
use Marwa\Framework\Supports\Mailer;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Supports\Storage;
use Marwa\Framework\Views\View as FrameworkView;
use Marwa\Router\Contract\ValidatorInterface as RouterValidatorInterface;
use Marwa\Support\Validation\RuleRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class CoreBindingsBootstrapper
{
    public function bootstrap(Application $app, Container $container): void
    {
        date_default_timezone_set((string) env('TIMEZONE', 'Asia/Dhaka'));

        $container->addShared(Application::class, $app);
        $container->addShared(Container::class, $container);

        $container->addShared(Config::class, fn() => new Config($app->basePath('config')));

        $container->addShared(Storage::class, fn() => new Storage(
            $app,
            $container->get(Config::class)
        ));

        $container->addShared(MenuRegistry::class);

        $container->addShared(EventDispatcherAdapter::class, fn() => new EventDispatcherAdapter(
            $container,
            $container->get(Config::class)
        ));

        $container->addShared(EventDispatcherInterface::class, fn() => $container->get(EventDispatcherAdapter::class));

        $container->addShared(ScrapbookCacheAdapter::class, fn() => new ScrapbookCacheAdapter(
            $app,
            $container->get(Config::class)
        ));

        $container->addShared(CacheInterface::class, fn() => $container->get(ScrapbookCacheAdapter::class));

        $container->addShared(Http::class, fn() => new Http(
            $app,
            $container->get(Config::class)
        ));

        $container->addShared(HttpClientInterface::class, fn() => $container->get(Http::class));

        $container->addShared(\Marwa\Framework\Contracts\AIManagerInterface::class, fn() => new \Marwa\Framework\Adapters\AI\AIManagerAdapter(
            $app,
            $container->get(Config::class)
        ));

        if (class_exists(\Memran\MarwaMcp\ServerFactory::class)) {
            $container->addShared(\Marwa\Framework\Contracts\MCP\MCPServerInterface::class, fn() => new \Marwa\Framework\Adapters\MCP\MCPAdapter(
                $app,
                $container->get(Config::class)
            ));
        }

        $container->addShared(\Marwa\Framework\Adapters\Process\ProcessAdapter::class, fn() => new \Marwa\Framework\Adapters\Process\ProcessAdapter(
            $app,
            $container->get(Config::class)
        ));

        $container->addShared(Mailer::class, fn() => new Mailer(
            $app,
            $container->get(\Marwa\Framework\Contracts\MailerAdapterInterface::class)
        ));

        $container->addShared(MailerInterface::class, fn() => $container->get(Mailer::class));

        $container->addShared(\Marwa\Framework\Contracts\MailerAdapterInterface::class, fn() => new \Marwa\Framework\Adapters\Mail\SymfonyMailerAdapter(
            $app,
            $container->get(Config::class)
        ));

        if (!Runtime::isConsole()) {
            $container->addShared(FrameworkView::class, fn() => new FrameworkView(
                $container->get(\Marwa\Framework\Adapters\ViewAdapter::class)
            ));
        }

        $container->addShared(NotificationManager::class, fn() => new NotificationManager(
            $app,
            $container->get(Config::class)
        ));

        $container->addShared(KafkaChannel::class, fn() => new KafkaChannel($app));

        $container->addShared(EncryptedSession::class, fn() => new EncryptedSession(
            $app,
            $container->get(Config::class)
        ));

        $container->addShared(SessionInterface::class, fn() => $container->get(EncryptedSession::class));

        $container->addShared(RuleRegistry::class);

        $container->addShared(RouterValidatorInterface::class, fn() => $container->get(RequestValidatorAdapter::class));

        $container->addShared(RequestValidatorAdapter::class, fn() => new RequestValidatorAdapter(
            new \Marwa\Support\Validation\RequestValidator($container->get(RuleRegistry::class)),
            $container->get(RuleRegistry::class)
        ));

        $container->addShared(Security::class, fn() => new Security(
            $app,
            $container->get(Config::class),
            $container->get(CacheInterface::class),
            $container->get(SessionInterface::class)
        ));

        $container->addShared(SecurityInterface::class, fn() => $container->get(Security::class));

        $container->addShared(RiskAnalyzer::class, fn () => new RiskAnalyzer(
            $app,
            $container->get(Config::class),
            $container->get(LoggerInterface::class)
        ));

        $container->addShared(ErrorHandlerAdapter::class, fn() => new ErrorHandlerAdapter(
            $app,
            $container->get(Config::class),
            $container->get(LoggerInterface::class)
        ));

        $container->addShared(ErrorHandlerBootstrapper::class, fn() => new ErrorHandlerBootstrapper(
            $app,
            $container->get(ErrorHandlerAdapter::class)
        ));

        $container->addShared(DatabaseBootstrapper::class, fn() => new DatabaseBootstrapper(
            $app,
            $container,
            $container->get(Config::class),
            $container->get(LoggerInterface::class)
        ));

        $container->addShared(AppBootstrapper::class, fn() => new AppBootstrapper(
            $app,
            $container->get(Config::class),
            $container->get(ProviderBootstrapper::class),
            $container->get(ErrorHandlerBootstrapper::class),
            $container->get(DatabaseBootstrapper::class),
            $container->get(ModuleBootstrapper::class)
        ));

        $container->addShared(ModuleBootstrapper::class, fn() => new ModuleBootstrapper(
            $app,
            $container,
            $container->get(Config::class)
        ));

        $container->addShared(CommandRegistry::class, fn() => new CommandRegistry(
            $app,
            $container,
            $container->get(LoggerInterface::class)
        ));

        $container->addShared(PsyshShellFactory::class, fn() => new PsyshShellFactory());

        $container->addShared(ShellFactoryInterface::class, fn() => $container->get(PsyshShellFactory::class));

        $container->addShared(CommandDiscovery::class, fn() => new CommandDiscovery(
            $app,
            $container->get(LoggerInterface::class)
        ));

        $container->addShared(QueueManager::class, fn() => new QueueManager(
            $app,
            $container->get(Config::class)
        ));
        $container->add(QueueInterface::class, fn() => $container->get(QueueManager::class)->resolve());

        $container->addShared(ScheduleStoreResolver::class, fn() => new ScheduleStoreResolver(
            $container->get(DatabaseBootstrapper::class),
            $container->get(CacheInterface::class)
        ));

        $container->addShared(ScheduleStoreResolverInterface::class, fn() => $container->get(ScheduleStoreResolver::class));

        $container->addShared(Scheduler::class, fn() => new Scheduler(
            $app,
            $container->get(LoggerInterface::class),
            $container->get(QueueInterface::class),
            $container->get(ScheduleStoreResolverInterface::class)
        ));

        $container->addShared(ConsoleApplication::class, fn() => new ConsoleApplication(
            ConsoleConfig::defaults($app)['name'],
            ConsoleConfig::defaults($app)['version']
        ));

        $container->addShared(ConsoleKernel::class, fn() => new ConsoleKernel(
            $app,
            $container->get(Config::class),
            $container->get(LoggerInterface::class),
            $container->get(AppBootstrapper::class),
            $container->get(CommandRegistry::class),
            $container->get(CommandDiscovery::class),
            $container->get(ConsoleApplication::class)
        ));

        $this->registerLogger($container, $app);
        $this->registerAuthorization($container);
    }

    private function registerLogger(Container $container, Application $app): void
    {
        $container->addShared(LoggerAdapter::class, fn() => (new LoggerAdapter(
            $app,
            $container->get(Config::class)
        ))->getLogger());

        $container->addShared(LoggerInterface::class, fn() => $container->get(LoggerAdapter::class));
    }

    private function registerAuthorization(Container $container): void
    {
        $container->addShared(PolicyRegistry::class);

        $container->addShared(Gate::class, fn() => (new Gate(
            $container->get(PolicyRegistry::class)
        ))->before(function ($user, $ability, $resource) {
            if ($user !== null && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            return null;
        }));

        $container->addShared(GateInterface::class, fn() => $container->get(Gate::class));

        $container->addShared(AuthManager::class, fn() => new AuthManager(
            $container->get(Gate::class)
        ));
    }
}
