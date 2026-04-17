<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use Marwa\Framework\Adapters\Validation\ValidationExceptionResponder;
use Marwa\Framework\Facades\Config;
use Marwa\Framework\Facades\View;
use Marwa\Router\Response;
use Marwa\Support\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final class RouterMiddleware implements MiddlewareInterface
{
    protected bool $debug = false;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->debug = (bool) env('APP_DEBUG', false);

        try {
            debugger()?->mark('dispatch start');
            $response = router()?->dispatch($request);
            debugger()?->mark('end');
            return $response;
        } catch (ValidationException $exception) {
            return $this->renderValidation($request, $exception);
        } catch (NotFoundException $ex) {
            return $this->render404($request);
        } catch (MethodNotAllowedException $e) {
            return Response::json(['error' => 'Method Not Allowed'], 405);
        }
    }

    private function render404(ServerRequestInterface $request): ResponseInterface
    {
        $accept = $request->getHeaderLine('Accept');
        $path = htmlspecialchars($request->getUri()->getPath(), ENT_QUOTES, 'UTF-8');
        $method = $request->getMethod();

        if (stripos($accept, 'application/json') !== false) {
            return Response::json([
                'status' => 404,
                'error' => 'Not Found',
                'method' => $method,
                'message' => sprintf('The requested path "%s" was not found on this server.', $path),
            ]);
        }

        $template = Config::get('app.error404.template');

        if ($template !== null && View::exists($template)) {
            return View::make($template, [
                'path' => $path,
                'method' => $method,
                'debug' => $this->debug,
            ])->withStatus(404);
        }

        return Response::html($this->renderHtml($path, $method));
    }

    private function renderHtml(string $path, string $method): string
    {
        $debug = $this->debug ? "<p style='color:#888'>Requested path: <code>{$path}</code></p>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>404 Not Found</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root {
      color-scheme: light dark;
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      background: rgba(0,0,0,.03);
    }
    .box {
      text-align: center;
      padding: 2rem 3rem;
      border-radius: 1rem;
      background: rgba(255,255,255,.8);
      box-shadow: 0 2px 10px rgba(0,0,0,.1);
    }
    h1 { font-size: 3rem; margin-bottom: .5rem; }
    p  { color: #666; }
  </style>
</head>
<body>
  <div class="box">
    <h1>404</h1>
    <p>Sorry, the page you are looking for was not found.</p>
    {$debug}
  </div>
</body>
</html>
HTML;
    }

    private function renderValidation(ServerRequestInterface $request, ValidationException $exception): ResponseInterface
    {
        return (new ValidationExceptionResponder())->toResponse($exception, $request);
    }
}
