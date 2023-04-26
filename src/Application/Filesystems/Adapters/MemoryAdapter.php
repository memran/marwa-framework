<?php
	
	
	namespace Marwa\Application\Filesystems\Adapters;
	
	use League\Flysystem\FilesystemAdapter;
	use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
	
	class MemoryAdapter implements AdapterInterface {
		
		protected $_adapter;
		
		public function setup() : void
		{
			// TODO: Implement setup() method.
		}
		
		public function buildAdapter() : void
		{
			$this->_adapter = new InMemoryFilesystemAdapter();
		}
		
		public function getType() : string
		{
			return 'memory';
		}
		
		public function getAdapter() : FilesystemAdapter
		{
			return $this->_adapter;
		}
	}
