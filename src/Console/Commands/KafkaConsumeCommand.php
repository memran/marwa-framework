<?php

declare(strict_types=1);

namespace Marwa\Framework\Console\Commands;

use Marwa\Framework\Config\NotificationConfig;
use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Contracts\KafkaConsumerInterface;
use Marwa\Framework\Notifications\Events\KafkaMessageReceived;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kafka:consume', description: 'Consume Kafka topics using the configured Kafka consumer.')]
final class KafkaConsumeCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('topic', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Kafka topic to consume')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of messages to consume before exiting', 0)
            ->addOption('group-id', null, InputOption::VALUE_REQUIRED, 'Kafka consumer group ID')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON lines for each received Kafka message')
            ->addOption('once', null, InputOption::VALUE_NONE, 'Consume a single batch and exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->config()->loadIfExists(NotificationConfig::KEY . '.php');
        $config = $this->config()->getArray(NotificationConfig::KEY, []);
        $kafka = is_array($config['channels']['kafka'] ?? null) ? $config['channels']['kafka'] : [];

        $consumerId = (string) ($kafka['consumer'] ?? KafkaConsumerInterface::class);
        if (!class_exists($consumerId) && !$this->container()->has($consumerId)) {
            $output->writeln('<error>Kafka consumer service is not configured.</error>');

            return self::FAILURE;
        }

        $consumer = $this->container()->get($consumerId);
        if (!$consumer instanceof KafkaConsumerInterface) {
            $output->writeln('<error>Configured Kafka consumer does not implement KafkaConsumerInterface.</error>');

            return self::FAILURE;
        }

        $topics = array_values(array_filter(array_map(
            static fn (mixed $topic): string => is_string($topic) ? trim($topic) : '',
            $input->getOption('topic') ?: ($kafka['topics'] ?? [$kafka['topic'] ?? 'notifications'])
        )));

        if ($topics === []) {
            $topics = ['notifications'];
        }

        $options = [
            'group_id' => (string) ($input->getOption('group-id') ?: ($kafka['groupId'] ?? 'marwa-framework')),
            'limit' => max(0, (int) $input->getOption('limit')),
            'once' => (bool) $input->getOption('once'),
            'json' => (bool) $input->getOption('json'),
            'kafka' => $kafka,
        ];

        $count = 0;
        $handler = function (array $message, string $topic) use ($output, $options, &$count): void {
            $count++;

            $event = new KafkaMessageReceived(payload: [
                'topic' => $topic,
                'message' => $message,
            ]);
            $this->app()->dispatch($event);

            if ($options['json']) {
                $output->writeln(json_encode([
                    'topic' => $topic,
                    'message' => $message,
                ], JSON_THROW_ON_ERROR));

                return;
            }

            $output->writeln(sprintf('<info>Kafka message received</info> topic=%s', $topic));
        };

        $received = $consumer->consume($topics, $handler, $options);

        return $received >= 0 ? self::SUCCESS : self::FAILURE;
    }
}
