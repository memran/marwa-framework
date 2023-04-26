<?php
	
	
	namespace Marwa\Application\Cache\Builders;
	
	use League\Flysystem\Filesystem;
	use League\Flysystem\Local\LocalFilesystemAdapter;
	
	class File implements BuilderInterface {
		
		/**
		 * @var string
		 */
		protected $_storage;
		/**
		 * @var int
		 */
		protected $_expire = 0;
		
		/**
		 * @return int
		 */
		public function getExpire()
		{
			return $this->_expire;
		}
		
		/**
		 * @param int $expire
		 */
		public function setExpire( int $expire )
		{
			$this->_expire = $expire;
		}
		
		/**
		 * @return string
		 */
		public function getType() : string
		{
			return 'file';
		}
		
		/**
		 * @return Flysystem2
		 */
		public function getAdapter()
		{
			// create Scrapbook KeyValueStore object
			return new Flysystem2($this->getFilesystem());
		}
		
		/**
		 * @return Filesystem
		 */
		protected function getFilesystem()
		{
			return new Filesystem($this->getLocalAdapter());
		}
		
		/**
		 * @return LocalFilesystemAdapter
		 */
		protected function getLocalAdapter()
		{
			return new LocalFilesystemAdapter(
				$this->getStorage()
			);
		}
		
		/**
		 * @return string
		 */
		public function getStorage() : string
		{
			return $this->_storage;
		}
		
		/**
		 * @param string $path
		 */
		public function setStorage( string $path ) : void
		{
			$this->_storage = $path;
		}
		
	}
