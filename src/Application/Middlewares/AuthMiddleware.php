<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Marwa\Application\Response;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	use Marwa\Application\Facades\Auth;
	
	
	class AuthMiddleware implements MiddlewareInterface {
		
		/**
		 * @param ServerRequestInterface $request
		 * @param RequestHandlerInterface $handler
		 * @return ResponseInterface
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			// determine authentication and/or authorization
			// ...
			//$auth = Auth::valid();
			
			// if user has auth, use the request handler to continue to the next
			// middleware and ultimately reach your route callable
			if ( Auth::valid() === true )
			{
				return $handler->handle($request);
			}
			
			// if user does not have auth, possibly return a redirect response,
			// this will not continue to any further middleware and will never
			// reach your route callable
			return Response::redirect('/');
		}
		
	}
