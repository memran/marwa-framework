<?php

declare(strict_types=1);

namespace Marwa\Framework\Mail;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Queue\QueuedJob;
use Marwa\Support\Str;

abstract class Mailable
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var array<string, mixed>
     */
    private array $templateConfig = [
        'path' => 'resources/views/emails',
        'autoPlainText' => true,
        'inlineCss' => true,
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function with(array $data): static
    {
        /** @var array<string, mixed> $newData */
        $newData = array_replace($this->data, $data);

        return new static($newData);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @param mixed $default
     */
    public function value(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function htmlTemplate(string $template, array $data = []): static
    {
        $this->data['__htmlTemplate'] = $template;

        return $this->with(array_merge($this->data, $data));
    }

    public function setTemplateConfig(array $config): self
    {
        $this->templateConfig = array_merge($this->templateConfig, $config);

        return $this;
    }

    protected function getTemplateConfig(): array
    {
        return $this->templateConfig;
    }

    protected function renderHtmlTemplate(string $template, array $data): string
    {
        $fullTemplate = $this->templateConfig['path'] . '/' . ltrim($template, '/');

        $viewResponse = view($fullTemplate, $data);
        if ($viewResponse === null) {
            throw new \RuntimeException(sprintf('Email template [%s] not found.', $fullTemplate));
        }

        $html = $viewResponse->getBody()->__toString();

        if (!empty($this->templateConfig['inlineCss'])) {
            $html = $this->inlineCss($html);
        }

        return $html;
    }

    protected function htmlToPlainText(string $html): string
    {
        $text = Str::stripTags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    protected function inlineCss(string $html): string
    {
        if (!preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            return $html;
        }

        $css = implode("\n", $matches[1]);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        if (!preg_match_all('/<([a-z]+)[^>]*class\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/\\1>/is', $html, $matchesTags, PREG_SET_ORDER)) {
            return $html;
        }

        foreach ($matchesTags as $tag) {
            $element = $tag[1];
            $classes = explode(' ', trim($tag[2]));
            $content = $tag[3];

            $inlineStyles = '';
            foreach ($classes as $class) {
                $class = trim($class);
                if ($class === '') {
                    continue;
                }

                if (preg_match('/\.' . $class . '\s*\{([^}]+)\}/s', $css, $match)) {
                    $styles = $match[1];
                    $styles = str_replace([';', "\n", ' '], [' ; ', ' ', ' '], $styles);
                    $styles = preg_replace('/\s+/', ' ', trim($styles));
                    $inlineStyles .= $styles . ' ';
                }
            }

            if ($inlineStyles !== '') {
                $styleAttr = 'style="' . trim($inlineStyles) . '"';
                $replacement = '<' . $element . ' class="' . $tag[2] . '" ' . $styleAttr . '>' . $content . '</' . $element . '>';
                $html = str_replace($tag[0], $replacement, $html);
            }
        }

        return $html;
    }

    abstract public function build(MailerInterface $mailer): MailerInterface;

    public function send(): int
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        $html = null;
        if (isset($this->data['__htmlTemplate'])) {
            $template = $this->data['__htmlTemplate'];
            $config = $this->templateConfig;

            $html = $this->renderHtmlTemplate($template, $this->data);

            if (!empty($config['autoPlainText'])) {
                $plainText = $this->htmlToPlainText($html);
                $html = $html . "\n\n" . $plainText;
            }
        }

        return $this->build($mailer)->send();
    }

    public function queue(?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $mailer->queue($this, $queue, $delaySeconds);
    }

    /**
     * Queue email to be sent at a specific timestamp
     */
    public function queueAt(int $timestamp, ?string $queue = null): QueuedJob
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $mailer->queueAt($this, $timestamp, $queue);
    }

    /**
     * Queue recurring email
     * @param array{expression: string, timezone?: string} $schedule
     */
    public function queueRecurring(array $schedule, ?string $queue = null): QueuedJob
    {
        /** @var MailerInterface $mailer */
        $mailer = app(MailerInterface::class);

        return $mailer->queueRecurring($this, $schedule, $queue);
    }

    /**
     * @return array{class: class-string, data: array<string, mixed>}
     */
    public function toQueuePayload(): array
    {
        return [
            'class' => static::class,
            'data' => $this->data,
            'templateConfig' => $this->templateConfig,
        ];
    }
}
