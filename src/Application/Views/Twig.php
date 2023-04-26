<?php
	declare( strict_types = 1 );
	
	namespace Marwa\Application\Views;
	
	use Exception;
	use Marwa\Application\Response;
	use Marwa\Application\Views\Interfaces\ViewServiceInterface;
	use Psr\Http\Message\ResponseInterface;
	use Twig\Environment;
	use Twig\Error\LoaderError;
	use Twig\Error\RuntimeError;
	use Twig\Error\SyntaxError;
	use Twig\Loader\FilesystemLoader;
	
	class Twig implements ViewServiceInterface {
		
		
		/**
		 * [protected description]
		 *
		 * @var Environment
		 */
		protected $engine;
	
		/**
		 * [protected description] template file extensions
		 *
		 * @var string
		 */
		protected $file_ext = 'twig';
		
		/**
		 * [protected description] cache path
		 *
		 * @var string|bool
		 */
		protected $cache_path = false;
		
		/**
		 * [protected description] cache filename
		 *
		 * @var string
		 */
		protected $cache_file;
		
		/**
		 * [protected description]
		 *
		 * @var bool
		 */
		protected $cache_enable = false;
		
		/**
		 * [protected description] expire time
		 *
		 * @var int
		 */
		protected $expire = 300;
		/**
		 * @var array
		 */
		protected $globals = [];
		/**
		 * @var bool
		 */
		protected $debug = false;
		/**
		 * @var string
		 */
		protected $theme_folder;
		
		/**
		 * Twig constructor.
		 * @param array $config
		 * @throws Exception
		 */
		public function __construct( array $config )
		{
			$this->setConfig($config);
		}
		
		/**
		 * @param array $config
		 * @throws Exception
		 */
		protected function setConfig( array $config )
		{
			if ( empty($config) ) throw new Exception('Twig configuration is empty');
			
			$this->setDebug($config['debug']);
			$this->setCachePath($config['cacheDir']);
			$this->expire($config['expire']);
			$this->setGlobalVariable($config['globals']);
		}
		
		/**
		 * [expire description] set expire
		 *
		 * @param int $ttl [description]
		 * @return self      [description]
		 */
		public function expire( int $ttl ) : self
		{
			$this->expire = $ttl;
			
			return $this;
		}
		
		/**
		 * @param array $global
		 */
		public function setGlobalVariable( array $global )
		{
			if ( !empty($global) && is_array($global) )
			{
				$this->globals = $global;
			}
		}
		
		/**
		 * @param array $items
		 */
		public function global( $items )
		{
			array_push($this->globals, $items);
		}
		
	
		/**
		 * [cache description]
		 *
		 * @return self [description]
		 */
		public function cache() : self
		{
			$this->cache_enable = true;
			
			return $this;
		}
		
		/**
		 * [extension description] set template extension
		 *
		 * @param string $ext [description]
		 * @return self        [description]
		 */
		public function extension( string $ext ) : self
		{
			$this->file_ext = $ext;
			
			return $this;
		}
		
		/**
		 * @param string $file
		 * @param array $args
		 * @return string
		 * @throws LoaderError
		 * @throws RuntimeError
		 * @throws SyntaxError
		 * @throws Exception
		 */
		public function raw( string $file, array $args = [] ) : string
		{
			/**
			 * Get Complete filename with extension
			 */
			$file = $this->getFullTemplateName($file);
			
			/**
			 * Check file exists or not
			 */
			$this->checkTemplateFileExists($file);
			
			$this->createEngine();
			
			return $this->engine->render($file, $args);
		}
		
		/**
		 * [loadEngine description] load engine
		 *
		 * @return void [description]
		 */
		public function createEngine() : void
		{
			if ( !isset($this->engine) )
			{
				$loader = new FilesystemLoader($this->getThemeFolder());
				$this->engine = new Environment(
					$loader, [
						       'cache' => $this->getCachePath(),
						       'debug' => $this->getDebug()
					       ]
				);
				$this->engine->addExtension(new TwigExtension());
				$this->getGlobalVariable();
			}
		}
		
		/**
		 * [getThemeFolder description] return theme folder name
		 *
		 * @return string [description]
		 */
		protected function getThemeFolder() : string
		{
			return $this->theme_folder;
		}
		
		/**
		 * @param string $folder
		 */
		public function setThemeFolder( string $folder )
		{
			$this->theme_folder = $folder;
		}
		
		/**
		 * @return bool|string
		 */
		protected function getCachePath()
		{
			return $this->cache_path;
		}
		
		/**
		 * @param string|bool $path
		 * @return $this
		 */
		public function setCachePath( $path )
		{
			if ( is_bool($path) && $path === true )
			{
				$this->cache();
			}
			else
			{
				$this->cache_path = $path;
			}
			
			return $this;
		}
		
		/**
		 * @return bool
		 */
		protected function getDebug()
		{
			return $this->debug;
		}
		
		/**
		 * @param bool $debug
		 */
		public function setDebug( $debug )
		{
			$this->debug = $debug;
		}
		
		/**
		 *
		 */
		protected function getGlobalVariable()
		{
			if ( !empty($this->globals) && is_array($this->globals) )
			{
				foreach ( $this->globals as $key => $val )
				{
					$this->engine->addGlobal($key, $val);
				}
			}
		}
		
		/**
		 * @param string $file
		 * @param array $args
		 * @param int $status
		 * @param array $headers
		 * @return ResponseInterface
		 * @throws LoaderError
		 * @throws RuntimeError
		 * @throws SyntaxError
		 * @throws Exception
		 */
		public function render( string $file,
		                        array $args = [],
		                        int $status = 200,
		                        array $headers = []
		) : ResponseInterface
		{
			/**
			 * Get Complete filename with extension
			 */
			$file = $this->getFullTemplateName($file);
			
			/**
			 * Check file exists or not
			 */
			$this->checkTemplateFileExists($file);
			
			/**
			 *  If render engine is not loaded then load it
			 */
			$this->createEngine();
			
			/**
			 *  If cache is enable globally then check file already cached or not. If yes then fetched file from cache
			 * otherwise render normally and store cache for further use
			 */
			if ( $this->cache_enable )
			{
				$this->setCacheFile($file);
				//if cache is false
				if ( !$this->getFromCache() )
				{
					$content = $this->engine->render($file, $args);
					$this->saveToCache($content);
				}
				else
				{
					$content = $this->getFromCache();
				}
				
				return $this->writeBody($content, $status, $headers);
			}
			
			/**
			 *  Send PSR-7 Response to the browser with template content
			 */
			return $this->writeBody($this->engine->render($file, $args), $status, $headers);
		}
		
		/**
		 * @param string $name
		 * @return string
		 */
		protected function getFullTemplateName( string $name )
		{
			
			return $name . "." . $this->file_ext;
		}
		
		/**
		 * @param string $file
		 * @return bool
		 * @throws Exception
		 */
		protected function checkTemplateFileExists( $file )
		{
			/**
			 * Template name is not found then throw exception
			 */
			if ( !file_exists($this->getThemeFolder() . DIRECTORY_SEPARATOR . $file) )
			{
				throw new Exception('Template name not found in directory. ' . $file);
			}
			
			return true;
		}
		
		/**
		 * [getCache description] get the content from cache
		 *
		 * @return null|string [description]
		 */
		public function getFromCache()
		{
			return cache($this->getCacheFile());
		}
		
		/**
		 * @return string
		 */
		protected function getCacheFile()
		{
			return $this->cache_file;
		}
		
		/**
		 * [setCacheFile description] set cache file name
		 *
		 * @param string $name [description]
		 */
		protected function setCacheFile( string $name )
		{
			$this->cache_file = md5($name);
		}
		
		/**
		 * [saveCache description] save the content in cache
		 *
		 * @param string $content [description]
		 * @return void          [description]
		 */
		public function saveToCache( string $content ) : void
		{
			cache($this->getCacheFile(), $content);
		}
		
		/**
		 * @param string $content
		 * @param int $status
		 * @param array $headers
		 * @return ResponseInterface
		 */
		public function writeBody( string $content, int $status = 200, array $headers = [] ) : ResponseInterface
		{
			return Response::html($content, $status, $headers);
		}
		
		/**
		 * @param array $args
		 * @return ResponseInterface
		 * @throws LoaderError
		 * @throws RuntimeError
		 * @throws SyntaxError
		 */
		public function error404( array $args = [] ) : ResponseInterface
		{
			return Response::html($this->engine->render('404', $args));
		}
		
		
	}
