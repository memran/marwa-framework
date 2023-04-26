<?php
	
	
	namespace Marwa\Application\Filesystems;
	
	use Exception;
	use League\Flysystem\Filesystem as FS;
	use Marwa\Application\Filesystems\Exceptions\FilesystemException;
	use Marwa\Application\Filesystems\Exceptions\InvalidArgumentException;
	
	class Filesystem implements FilesystemInterface {
		
		/**
		 * @var string
		 */
		protected $_disk = 'local';
		
		/**
		 * @var FS
		 */
		protected $_fs;
		
		/**
		 * @var array|mixed
		 */
		protected $_config = [];
		
		/**
		 * Filesystem constructor.
		 * @param array $storage_config
		 * @throws InvalidArgumentException
		 */
		public function __construct( array $storage_config = [] )
		{
			$this->_config = $this->getStorageConfig($storage_config);
		}
		
		/**
		 * @param  $config
		 * @return mixed
		 * @throws InvalidArgumentException
		 */
		protected function getStorageConfig( $config ) : array
		{
			if ( empty($config) )
			{
				throw new InvalidArgumentException('Store configuration is empty!');
			}
			
			return $config;
		}
		
		/**
		 * @param string $disk
		 * @return $this|FilesystemInterface
		 * @throws InvalidArgumentException
		 */
		public function disk( string $disk ) : FilesystemInterface
		{
			if ( is_null($disk) || empty($disk) )
			{
				throw new InvalidArgumentException('Disk name is null');
			}
			
			$this->_disk = $disk;
			
			return $this;
		}
		
		/**
		 * @param  $name
		 * @param  $arguments
		 * @return mixed
		 * @throws Exception
		 */
		public function __call( $name, $arguments )
		{
			$filesystem = $this->getFilesystem();
			if ( method_exists($filesystem, $name) )
			{
				return $filesystem->$name(...$arguments);
			}
			else
			{
				throw new Exception('Method does not exists :' . $name);
			}
		}
		
		/**
		 * @return FS
		 * @throws FilesystemException
		 */
		public function getFilesystem()
		{
			if ( is_null($this->_fs) )
			{
				$this->createFilesystem();
			}
			
			return $this->_fs;
		}
		
		/**
		 *
		 */
		public function createFilesystem() : void
		{
			try
			{
				$adapter = FactoryAdapter::create($this->getDisk());
				$builder = new AdapterDirector($adapter, $this->getAdapterConfig());
				$this->_fs = new FS($builder->build()->getAdapter());
			} catch ( \Throwable $th )
			{
				throw new FilesystemException('Failed to Create Filesystem');
			}
		}
		
		/**
		 * @return string
		 * @throws FilesystemException
		 */
		public function getDisk() : string
		{
			if ( is_null($this->_disk) || empty($this->_disk) )
			{
				throw new FilesystemException('Disk name can not null');
			}
			
			return $this->_disk;
		}
		
		/**
		 * @return array
		 * @throws FilesystemException
		 */
		public function getAdapterConfig() : array
		{
			if ( array_key_exists($this->getDisk(), $this->_config) )
			{
				return $this->_config[ $this->getDisk() ];
			}
		}
	}
