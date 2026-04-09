<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Marwa\Framework\Config\AppConfig;
use Marwa\Framework\Supports\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final class DebugbarMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->config->getBool(AppConfig::KEY . '.debugbar', false)) {
            return $response;
        }

        $contentType = $response->getHeaderLine('Content-Type');

        if (stripos($contentType, 'text/html') === false) {
            return $response;
        }

        $bar = debugger();
        if ($bar === null) {
            return $response;
        }

        $barHtml = (new \Marwa\DebugBar\Renderer($bar))->render();
        if ($barHtml === '') {
            return $response;
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $html = (string) $body;

        $pos = stripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos) . $barHtml . substr($html, $pos);
        } else {
            $html .= $barHtml;
        }

        $body->rewind();
        $body->write($html);

        return $response
            ->withBody($body)
            ->withHeader('Content-Length', (string) strlen($html));
    }
}
