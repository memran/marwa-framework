<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Exception;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class MethodNotFoundMiddleware extends CorsMiddleware {
		
		protected $exception;
		
		
		function __construct( Exception $exception )
		{
			$this->exception = $exception;
		}
		
		/**
		 * {@inheritdoc}
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			//logger($request->getHeader('Sec-Fetch-Mode')[0]);
			if ( $request->getMethod() === "OPTIONS" && $request->hasHeader('Origin') && $request->getHeader('Sec-Fetch-Mode')[0] == 'cors' )
			{
				return $this->preFlightRequest($request);
			}
			// throw $this->exception;
			//return Response::html($this->getTemplate(),404);
			return view('404', ['title' => '404 Error','message'=>'Sorry, Page not Found']);
		}
		
		
	}
