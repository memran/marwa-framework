<?php


	namespace Marwa\Application\Request;

	use Exception;
	use Laminas\Diactoros\ServerRequestFactory;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Psr\Http\Message\RequestInterface;


	class Psr7Request implements Psr7Interface {

		/**
		 * [protected description]
		 *
		 * @var string
		 */
		protected $url;
		/**
		 * [$server description]
		 *
		 * @var string
		 */
		protected $server = 'default';
		/**
		 * [protected description]
		 *
		 * @var ServerRequestFactory
		 */
		private $request;

		/**
		 * Psr7Request constructor.
		 * @throws InvalidArgumentException
		 */
		public function __construct()
		{
			//assign global request uri
			if ( array_key_exists('REQUEST_URI', $_SERVER) )
			{
				$this->url = ( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
				//trim url if any extra path exists
				$this->trimBase();
			}
			$this->setRequest(ServerRequestFactory::fromGlobals(
					$_SERVER,
				    $_GET,
				    $_POST,
				    $_COOKIE,
				    $_FILES));
		}

		/**
		 * @throws InvalidArgumentException
		 */
		private function trimBase() : void
		{
			//get the application base path
			$base = parse_url(base_url(), PHP_URL_PATH);
			//remove the extra path from url
			$route = substr($this->getUrl(), ( strlen($base) ));

			$path = sprintf('/%s', trim($route, '/'));
			$this->setRequestUrl($path);
		}

		/**
		 * @return string
		 */
		protected function getUrl() : string
		{
			return $this->url;
		}

		/**
		 * @param $path
		 */
		protected function setRequestUrl( $path ) : void
		{
			$_SERVER['REQUEST_URI'] = trim($path);
		}



		/**
		 * @param $request
		 * @return $this|\Marwa\Application\Request\Psr7Interface
		 * @throws Exception
		 */
		public function setRequest( $request ) : Psr7Interface
		{
			if ( $request instanceof RequestInterface )
			{
				$this->request = $request;
			}
			else
			{
				throw new Exception('Incompatible PSR7 Request Class');
			}

			return $this;
		}
		/**
		 * @return RequestInterface
		 */
		public function getRequest() : RequestInterface
		{
			return $this->request;
		}

	}
