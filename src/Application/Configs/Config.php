<?php
	
	
	namespace Marwa\Application\Configs;
	
	use Marwa\Application\Configs\Exceptions\FileNotFoundException;
	use Marwa\Application\Configs\Exceptions\InvalidExtensionException;
	use Marwa\Application\Configs\Interfaces\ConfigInterface;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	
	class Config implements ConfigInterface {
		
		/**
		 * @var ConfigInterface
		 */
		private static $__instance;
		/**
		 * @var string
		 */
		private $__filename;
		/**
		 * @var string
		 */
		private $__fileType;
		/**
		 * @var string
		 */
		private $__dir = '';
		
		/**
		 * Config constructor.
		 * @param string|null $filename
		 * @throws FileNotFoundException
		 */
		private function __construct( string $filename = null )
		{
			if ( !is_null($filename) )
			{
				$this->file($filename);
			}
		}
		
		/**
		 * @param string $filename
		 * @return $this|ConfigInterface
		 * @throws FileNotFoundException
		 * @throws InvalidExtensionException
		 */
		public function file( string $filename ) : ConfigInterface
		{
			$this->checkFile($filename);
			if ( !file_exists($this->getFile()) )
			{
				throw new FileNotFoundException('File does not exists ' . $this->getFile());
			}
			
			return $this;
		}
		
		/**
		 * @param string $filename
		 * @return bool
		 * @throws InvalidExtensionException
		 */
		protected function checkFile( string $filename ) : bool
		{
			$fileParts = pathinfo($filename);
			//check file extension is php
			if ( $fileParts['extension'] != 'php' )
			{
				throw new InvalidExtensionException('Invalid File extension' . $filename);
			}
			$this->setType($fileParts['extension']);
			$this->setFile($fileParts['basename']);
			
			return true;
		}
		
		/**
		 * @param string $type
		 */
		public function setType( string $type )
		{
			$this->__fileType = trim($type);
		}
		
		/**
		 * @param $filename
		 */
		protected function setFile( $filename ) : void
		{
			$this->__filename = $this->getConfigDir() . DIRECTORY_SEPARATOR . $filename;
		}
		
		/**
		 * @return string
		 */
		public function getConfigDir() : string
		{
			return rtrim($this->__dir, '/');
		}
		
		/**
		 * @return string
		 */
		public function getFile() : string
		{
			return $this->__filename;
		}
		
		/**
		 * @param string|null $filename
		 * @return ConfigInterface
		 * @throws FileNotFoundException
		 */
		public static function getInstance( ?string $filename = null ) : ConfigInterface
		{
			if ( self::$__instance == null )
			{
				return new Config($filename);
			}
			
			return self::$__instance;
		}
		
		/**
		 * @param string|null $filename
		 * @return array
		 * @throws FileNotFoundException
		 * @throws InvalidExtensionException
		 */
		public function load( ?string $filename = null ) : array
		{
			if ( !is_null($filename) )
			{
				$this->file($filename);
			}
			
			if ( is_null($this->getFile()) )
			{
				throw new FileNotFoundException("File name is empty");
			}
			
			return ConfigFactory::create($this->getType())->setFile($this->getFile())->load();
		}
	
		/**
		 * @return string
		 */
		public function getType() : string
		{
			return $this->__fileType;
		}
		
		/**
		 * @param string $path
		 * @return $this|ConfigInterface
		 * @throws InvalidArgumentException
		 */
		public function setConfigDir( string $path ) : ConfigInterface
		{
			if ( empty($path) || is_null($path) )
			{
				throw new InvalidArgumentException('Config Directory path is empty');
			}
			$this->__dir = $path;
			
			return $this;
		}
	}
