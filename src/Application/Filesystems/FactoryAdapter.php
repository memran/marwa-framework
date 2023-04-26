<?php
	
	
	namespace Marwa\Application\Filesystems;
	
	use Marwa\Application\Filesystems\Adapters\AdapterInterface;
	use Marwa\Application\Filesystems\Adapters\AwsAdapter;
	use Marwa\Application\Filesystems\Adapters\FtpAdapter;
	use Marwa\Application\Filesystems\Adapters\LocalAdapter;
	use Marwa\Application\Filesystems\Adapters\MemoryAdapter;
	use Marwa\Application\Filesystems\Adapters\SftpAdapter;
	
	class FactoryAdapter implements FactoryFileInterface {
		
		/**
		 * @param string $type
		 * @return
		 */
		public static function create( string $type ) : AdapterInterface
		{
			switch ( strtolower($type) )
			{
				case 'ftp':
					return new FtpAdapter();
				case 'sftp':
					return new SftpAdapter();
				case 's3':
					return new AwsAdapter();
				case 'memory':
					return new MemoryAdapter();
				default:
					return new LocalAdapter();
			}
		}
		
	}
