<?php

declare(strict_types=1);

/**
 * Service Helper Functions
 */

if (!function_exists('event')) {
    function event(\Marwa\Framework\Adapters\Event\AbstractEvent $event): void
    {
        $bus = app(\Marwa\Framework\Adapters\Event\EventDispatcherAdapter::class);

        if (!$bus instanceof \Marwa\Framework\Contracts\EventDispatcherInterface) {
            throw new \RuntimeException('Event dispatcher binding is invalid.');
        }

        $bus->dispatch($event);
    }
}

if (!function_exists('logger')) {
    function logger(): \Psr\Log\LoggerInterface
    {
        return app(\Psr\Log\LoggerInterface::class);
    }
}

if (!function_exists('mailer')) {
    function mailer(): \Marwa\Framework\Contracts\MailerInterface
    {
        return app(\Marwa\Framework\Supports\Mailer::class);
    }
}

if (!function_exists('mail')) {
    function mail(): \Marwa\Framework\Contracts\MailerInterface
    {
        return app(\Marwa\Framework\Supports\Mailer::class);
    }
}

if (!function_exists('mail_fake')) {
    function mail_fake(): \Marwa\Framework\Mail\MailFake
    {
        return app(\Marwa\Framework\Mail\MailFake::class);
    }
}

if (!function_exists('notification')) {
    function notification(): \Marwa\Framework\Notifications\NotificationManager
    {
        return app(\Marwa\Framework\Notifications\NotificationManager::class);
    }
}

if (!function_exists('router')) {
    function router(): mixed
    {
        return app(\Marwa\Framework\Adapters\RouterAdapter::class);
    }
}

if (!function_exists('module')) {
    function module(string $slug): \Marwa\Module\ModuleHandle
    {
        return app()->module($slug);
    }
}

if (!function_exists('has_module')) {
    function has_module(string $slug): bool
    {
        return app()->hasModule($slug);
    }
}

if (!function_exists('menu')) {
    function menu(): \Marwa\Framework\Navigation\MenuRegistry
    {
        return app(\Marwa\Framework\Navigation\MenuRegistry::class);
    }
}

if (!function_exists('dispatch')) {
    function dispatch(object $event): object
    {
        return app()->dispatch($event);
    }
}

if (!function_exists('http')) {
    function http(): \Marwa\Framework\Contracts\HttpClientInterface
    {
        /** @var \Marwa\Framework\Contracts\HttpClientInterface $http */
        $http = app(\Marwa\Framework\Contracts\HttpClientInterface::class);

        return $http;
    }
}

if (!function_exists('ai')) {
    function ai(): \Marwa\Framework\Contracts\AIManagerInterface
    {
        return app(\Marwa\Framework\Contracts\AIManagerInterface::class);
    }
}

if (!function_exists('ai_complete')) {
    /**
     * @param array<string, mixed> $options
     */
    function ai_complete(string $prompt, array $options = []): mixed
    {
        return ai()->complete($prompt, $options);
    }
}

if (!function_exists('ai_conversation')) {
    /**
     * @param list<array<string, mixed>> $messages
     */
    function ai_conversation(array $messages = []): mixed
    {
        return ai()->conversation($messages);
    }
}

if (!function_exists('ai_embed')) {
    /**
     * @param list<string> $texts
     * @param array<string, mixed> $options
     */
    function ai_embed(array $texts, array $options = []): mixed
    {
        return ai()->embed($texts, $options);
    }
}

if (!function_exists('mcp')) {
    function mcp(): \Marwa\Framework\Contracts\MCP\MCPServerInterface
    {
        if (!app()->has(\Marwa\Framework\Contracts\MCP\MCPServerInterface::class)) {
            throw new \RuntimeException('MCP support is not installed. Require memran/marwa-mcp to use mcp().');
        }

        return app(\Marwa\Framework\Contracts\MCP\MCPServerInterface::class);
    }
}

if (!function_exists('process')) {
    /**
     * @param array<string, mixed>|callable $options
     */
    function process(?string $command = null, array|callable $options = []): mixed
    {
        $adapter = app(\Marwa\Framework\Adapters\Process\ProcessAdapter::class);

        if ($command === null) {
            return $adapter;
        }

        if (is_callable($options)) {
            $callback = $options;
            $adapter->onComplete(function($result) use ($callback) {
                $callback($result->getOutput());
            });
            return $adapter->execute($command);
        }

        return $adapter->execute($command, $options);
    }
}
