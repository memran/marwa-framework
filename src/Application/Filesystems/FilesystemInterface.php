<?php
	
	
	namespace Marwa\Application\Filesystems;
	
	
	interface FilesystemInterface {
		
		public function disk( string $disk ) : FilesystemInterface;
		
		public function getDisk() : string;
		
		public function getAdapterConfig() : array;
		
	}
