<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Marwa\Application\Response;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class CsrfTokenMiddleware implements MiddlewareInterface {
		
		/**
		 * {@inheritdoc}
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			if ( $request->getMethod() === 'POST' )
			{
				$session = app('session');
				if ( $session == null )
				{
					return Response::error("Session Attribute not Found.");
				}
				$body = $request->getParsedBody();
				
				//check if array key exists
				if ( array_key_exists('__csrf_value', $body) )
				{
					$csrf_value = $request->getParsedBody()['__csrf_value'];
					if ( $csrf_value == null || !$session->isValid($csrf_value) )
					{
						return Response::html("This looks like a cross-site request forgery.");
					}
				}
				else
				{
					return Response::html("Invalid Request.");
				}
				
			}
			
			return $handler->handle($request);
			
		}
	}
