<?php
	
	
	namespace Marwa\Application\Filesystems\Adapters;
	
	use League\Flysystem\FilesystemAdapter;
	use League\Flysystem\Local\LocalFilesystemAdapter;
	use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
	
	class LocalAdapter implements AdapterInterface {
		
		/**
		 * @var array
		 */
		protected $visibility = [];
		/**
		 * @var LocalFilesystemAdapter
		 */
		protected $_adapter;
		/**
		 * @var array|int|object|string
		 */
		private $storage_path;
		
		/**
		 * @return string
		 */
		public function getType() : string
		{
			return 'local';
		}
		
		/**
		 * @param $path
		 */
		public function setStorage( $path )
		{
			$this->storage_path = $path;
		}
		
		/**
		 *
		 */
		public function buildAdapter() : void
		{
			$this->_adapter = new LocalFilesystemAdapter($this->getStorage(), $this->getVisibility());
		}
		
		/**
		 * @return string
		 */
		protected function getStorage() : string
		{
			return $this->storage_path;
		}
		
		/**
		 * @return PortableVisibilityConverter
		 */
		protected function getVisibility()
		{
			return PortableVisibilityConverter::fromArray($this->visibility);
		}
		
		/**
		 * @param array $visibility
		 */
		public function setVisibility( array $visibility )
		{
			$this->visibility[] = $visibility;
		}
		
		/**
		 * @return mixed
		 */
		public function getAdapter() : FilesystemAdapter
		{
			// The internal adapter
			return $this->_adapter;
		}
	}
