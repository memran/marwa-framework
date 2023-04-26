<?php
	
	
	namespace Marwa\Application\Filesystems\Adapters;
	
	use League\Flysystem\FilesystemAdapter;
	
	interface AdapterInterface {
		
		public function getType() : string;
		
		public function buildAdapter() : void;
		
		public function getAdapter() : FilesystemAdapter;
	}
