<?php
	
	namespace Marwa\Application\Middlewares;
	
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class LocalizationMiddleware implements MiddlewareInterface {
		
		/**
		 * @var array Allowed languages
		 */
		private $languages = [];
		
		/**
		 * [$defaultLocale description]
		 *
		 * @var string
		 */
		private $defaultLocale = 'en';
		
		/**
		 * @var bool Returns a redirect response or not
		 */
		private $redirect = false;
		
		/**
		 * Process a server request and return a response.
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			//list of supported locale assignment
			$this->languages = Config('app')['supportLocale'];
			//set default locale
			$this->defaultLocale = Config('app')['locale'];
			
			//enable locale detection from path
			if ( !Config('app')['detectLocaleFromPath'] )
			{
				$this->setLangSession($this->defaultLocale);
				
				return $handler->handle($request);
			}
			//fetch request uri
			$uri = $request->getUri();
			//parsing locale from request
			$language = $this->detectLangFromPath($uri->getPath());
			//set language in session
			$this->setLangSession($language);
			
			//return handler
			return $handler->handle($request);
		}
		
		/**
		 * [setLangSession description]
		 *
		 * @param string $language [description]
		 */
		private function setLangSession( string $language ) : void
		{
			if ( !is_null($language) )
			{
				session('lang', $language);
			}
		}
		
		/**
		 * @param string $path
		 * @return mixed|string
		 */
		private function detectLangFromPath( string $path )
		{
			$dirs = explode('/', ltrim($path, '/'), 2);
			$first = strtolower(array_shift($dirs));
			if ( !empty($first) && in_array($first, $this->languages, true) )
			{
				return $first;
			}
			else
			{
				return $this->defaultLocale;
			}
		}
		
	}
