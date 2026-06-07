<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Adapters;

use Marwa\ErrorHandler\Contracts\RendererInterface;
use Marwa\Framework\Adapters\ErrorViewRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorViewRendererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/error-view-renderer-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            foreach (glob($this->tempDir . '/*') as $file) {
                unlink($file);
            }
            rmdir($this->tempDir);
        }
    }

    public function testRenderExceptionFallsBackOnTemplateFailure(): void
    {
        $fallback = $this->createMock(RendererInterface::class);
        $fallback->expects($this->once())
            ->method('renderException');

        $renderer = new ErrorViewRenderer(
            fallback: $fallback,
            template: 'nonexistent/template.twig',
        );

        ob_start();
        $renderer->renderException(
            new RuntimeException('Test error'),
            'TestApp',
            true,
        );
        ob_end_clean();
    }

    public function testRenderGenericFallsBackOnTemplateFailure(): void
    {
        $fallback = $this->createMock(RendererInterface::class);
        $fallback->expects($this->once())
            ->method('renderGeneric');

        $renderer = new ErrorViewRenderer(
            fallback: $fallback,
            template: 'nonexistent/template.twig',
        );

        ob_start();
        $renderer->renderGeneric('TestApp');
        ob_end_clean();
    }

    public function testRenderCliDelegatesToFallback(): void
    {
        $fallback = $this->createMock(RendererInterface::class);
        $fallback->expects($this->once())
            ->method('renderCli');

        $renderer = new ErrorViewRenderer(
            fallback: $fallback,
            template: 'nonexistent/template.twig',
        );

        $renderer->renderCli(
            new RuntimeException('Test error'),
            'TestApp',
            true,
        );
    }
}
