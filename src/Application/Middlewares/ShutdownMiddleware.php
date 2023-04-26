<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class ShutdownMiddleware implements MiddlewareInterface {
		
		/**
		 * Process a server request and return a response.
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			$maintainance = (bool) env('MAINTAINANCE');
			if ( $maintainance )
			{
				return view('maintainance', ['title' => 'Site Maintenance']);
			}
			
			return $handler->handle($request);
		}
		
		protected function getTemplate()
		{
			return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>503. Site under maintenance</title>
            <style>html{font-family: sans-serif;}</style>
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>
        <body>
            <center><h1>Site under maintenance</h1></center>
        </body>
        </html>';
		}
	}
