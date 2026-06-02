<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use GuzzleHttp\Psr7\Utils;
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

        return $this->injectDebugBar($response, $barHtml);
    }

    private function injectDebugBar(ResponseInterface $response, string $barHtml): ResponseInterface
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $output = fopen('php://temp', 'w+b');

        if ($output === false) {
            return $response;
        }

        $needle = '</body>';
        $needleLength = strlen($needle);
        $carry = '';
        $injected = false;

        while (!$body->eof()) {
            $chunk = $body->read(8192);

            if ($chunk === '') {
                break;
            }

            $search = $carry . $chunk;

            if (!$injected) {
                $position = stripos($search, $needle);

                if ($position !== false) {
                    fwrite($output, substr($search, 0, $position));
                    fwrite($output, $barHtml);
                    fwrite($output, substr($search, $position));
                    $injected = true;
                    $carry = '';

                    continue;
                }

                $carryLength = max(0, $needleLength - 1);

                if (strlen($search) <= $carryLength) {
                    $carry = $search;

                    continue;
                }

                $writeLength = strlen($search) - $carryLength;
                fwrite($output, substr($search, 0, $writeLength));
                $carry = substr($search, -$carryLength);

                continue;
            }

            fwrite($output, $chunk);
        }

        if (!$injected) {
            fwrite($output, $carry . $barHtml);
        }

        rewind($output);
        $rewrittenBody = Utils::streamFor($output);

        return $response
            ->withBody($rewrittenBody)
            ->withHeader('Content-Length', (string) ($rewrittenBody->getSize() ?? 0));
    }
}
