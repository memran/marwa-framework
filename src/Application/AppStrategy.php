<?php declare(strict_types=1);

namespace Marwa\Application;

use Exception;
use League\Route\{ContainerAwareInterface,ContainerAwareTrait};
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use League\Route\Strategy\{StrategyInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Marwa\Application\Middlewares\MethodNotFoundMiddleware;

class AppStrategy implements ContainerAwareInterface, StrategyInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface
    {

        return call_user_func_array(
            $route->getCallable($this->getContainer()),
            [$request,$route->getVars()]
        );

    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception) : MiddlewareInterface
    {
        return $this->throwExceptionMiddleware($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception) : MiddlewareInterface
    {
        return $this->throwExceptionMiddleware($exception);
    }

    /**
     * Return a middleware that simply throws and exception.
     *
     * @param \Exception $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    protected function throwExceptionMiddleware(Exception $exception) : MiddlewareInterface
    {
          return new MethodNotFoundMiddleware($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionHandler() : MiddlewareInterface
    {
        return $this->getThrowableHandler();
    }

    /**
     * {@inheritdoc}
     */
    public function getThrowableHandler() : MiddlewareInterface
    {
        return new class implements MiddlewareInterface
        {
            /**
             * {@inheritdoc}
             */
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ) : ResponseInterface {
                try {
                    return $requestHandler->handle($request);
                } catch (Throwable $e) {
                    throw $e;
                }
            }
        };
    }
}
