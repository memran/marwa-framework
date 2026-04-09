<?php

declare(strict_types=1);

namespace Marwa\Framework\Security;

use Marwa\Framework\Application;
use Marwa\Framework\Config\SecurityConfig;
use Marwa\Framework\Supports\Config;
use Marwa\Framework\Supports\File;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class RiskAnalyzer
{
    /**
     * @var array{
     *     enabled: bool,
     *     logPath: string,
     *     pruneAfterDays: int,
     *     topCount: int
     * }
     */
    private array $settings;

    public function __construct(
        private Application $app,
        private Config $config,
        private LoggerInterface $logger
    ) {
        $this->config->loadIfExists(SecurityConfig::KEY . '.php');
        $this->settings = array_replace_recursive(SecurityConfig::defaults($this->app), $this->config->getArray(SecurityConfig::KEY, []))['risk'];
    }

    public function enabled(): bool
    {
        return (bool) $this->settings['enabled'];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function record(string $category, string $message, array $context = [], int $score = 50): void
    {
        if (!$this->enabled()) {
            return;
        }

        $signal = [
            'timestamp' => gmdate(DATE_ATOM),
            'category' => $category,
            'message' => $message,
            'score' => max(0, min(100, $score)),
            'context' => $context,
        ];

        File::path($this->logPath())->append(json_encode($signal, JSON_THROW_ON_ERROR) . PHP_EOL);

        $this->logger->warning($message, array_merge($context, [
            'security_category' => $category,
            'security_score' => $signal['score'],
        ]));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function recordRequest(ServerRequestInterface $request, string $category, string $message, array $context = [], int $score = 50): void
    {
        $this->record($category, $message, array_merge([
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'host' => $request->getUri()->getHost(),
            'ip' => (string) ($request->getServerParams()['REMOTE_ADDR'] ?? ''),
            'userAgent' => $request->getHeaderLine('User-Agent'),
        ], $context), $score);
    }

    /**
     * @return array{
     *     total: int,
     *     byCategory: array<string, int>,
     *     byScore: array{high: int, medium: int, low: int},
     *     latest: list<array<string, mixed>>
     * }
     */
    public function report(?int $sinceHours = null): array
    {
        $entries = $this->entries();

        if ($sinceHours !== null && $sinceHours > 0) {
            $cutoff = time() - ($sinceHours * 3600);
            $entries = array_values(array_filter($entries, static function (array $entry) use ($cutoff): bool {
                $timestamp = strtotime((string) ($entry['timestamp'] ?? '')) ?: 0;

                return $timestamp >= $cutoff;
            }));
        }

        $byCategory = [];
        $byScore = ['high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($entries as $entry) {
            $category = (string) ($entry['category'] ?? 'unknown');
            $score = (int) ($entry['score'] ?? 0);
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;

            if ($score >= 80) {
                $byScore['high']++;
            } elseif ($score >= 40) {
                $byScore['medium']++;
            } else {
                $byScore['low']++;
            }
        }

        $topCount = max(1, (int) $this->settings['topCount']);

        return [
            'total' => count($entries),
            'byCategory' => $byCategory,
            'byScore' => $byScore,
            'latest' => array_slice(array_reverse($entries), 0, $topCount),
        ];
    }

    public function prune(?int $olderThanDays = null): int
    {
        if (!$this->enabled()) {
            return 0;
        }

        $olderThanDays ??= max(1, (int) $this->settings['pruneAfterDays']);
        $cutoff = time() - ($olderThanDays * 86400);
        $entries = $this->entries();
        $kept = [];
        $removed = 0;

        foreach ($entries as $entry) {
            $timestamp = strtotime((string) ($entry['timestamp'] ?? '')) ?: 0;

            if ($timestamp > 0 && $timestamp < $cutoff) {
                $removed++;
                continue;
            }

            $kept[] = $entry;
        }

        $this->writeEntries($kept);

        return $removed;
    }

    public function logPath(): string
    {
        return (string) $this->settings['logPath'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entries(): array
    {
        if (!is_file($this->logPath())) {
            return [];
        }

        $lines = file($this->logPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $entries = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private function writeEntries(array $entries): void
    {
        $path = $this->logPath();
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }

        $contents = '';
        foreach ($entries as $entry) {
            $contents .= json_encode($entry, JSON_THROW_ON_ERROR) . PHP_EOL;
        }

        file_put_contents($path, $contents, LOCK_EX);
    }
}
