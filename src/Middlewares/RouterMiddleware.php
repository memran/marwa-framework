<?php

declare(strict_types=1);

namespace Marwa\Framework\Middlewares;

use League\Route\Http\Exception\{NotFoundException, MethodNotAllowedException};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

use Marwa\Router\Response;

final class RouterMiddleware implements MiddlewareInterface
{
  protected $debug = false;

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $this->debug = env('APP_DEBUG');

    try {
      debugger()?->mark('dispatch start');
      $response = router()?->dispatch($request);
      debugger()?->mark('end');
      return $response;
    } catch (NotFoundException $ex) {
      return $this->render404($request);
    } catch (MethodNotAllowedException $e) {
      return Response::json(['error' => 'Method Not Allowed'], 405);
    }
  }
  /**
   * Generate a standard 404 Not Found response.
   */
  private function render404(ServerRequestInterface $request): ResponseInterface
  {

    $accept = $request->getHeaderLine('Accept');

    $path = htmlspecialchars($request->getUri()->getPath(), ENT_QUOTES, 'UTF-8');

    // JSON response if requested by client
    if (stripos($accept, 'application/json') !== false) {
      $payload = [
        'status'  => 404,
        'error'   => 'Not Found',
        'method'  => $request->getMethod(),
        'message' => sprintf('The requested path "%s" was not found on this server.', $path),
      ];
      return Response::json($payload);
    }

    // Otherwise, HTML response
    $html = $this->renderHtml($path);
    return Response::html($html);
  }

  /**
   * Render a simple HTML 404 page.
   */
  private function renderHtml(string $path): string
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
}
