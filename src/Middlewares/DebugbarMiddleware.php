<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Exceptions\NotFoundException;

final class DebugbarMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $contentType = $response->getHeaderLine('Content-Type');

        if (stripos($contentType, 'text/html') === false) {
            return $response;
        }

        if (Runtime::isWeb()) {

            if (env('DEBUGBAR_ENABLED', false)) {
                $bar = debugger();
                if (is_null($bar)) {
                    throw new NotFoundException("Debugbar not found");
                }
                $barHtml = (new \Marwa\DebugBar\Renderer(debugger()))->render();
                if ($barHtml === '') {
                    return $response;
                }
                // read the body into a string
                $body = $response->getBody();
                if ($body->isSeekable()) {
                    $body->rewind();
                }
                $html = (string) $body;
                // inject before </body> (case-insensitive); fallback: append
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
            // If DEBUGBAR_ENABLED is false, return the response
            return $response;
        } else

            return $response;
    }
}
