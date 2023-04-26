<?php
	
	
	namespace Marwa\Application\Filesystems\Adapters;
	
	use League\Flysystem\FilesystemAdapter;
	use League\Flysystem\FTP\FtpAdapter as FAdapter;
	use League\Flysystem\FTP\FtpConnectionOptions;
	
	class FtpAdapter implements AdapterInterface {
		
		/**
		 * @var FilesystemAdapter
		 */
		protected $_adapter;
		/**
		 * @var array
		 */
		protected $_config;
		
		/**
		 * @return string
		 */
		public function getType() : string
		{
			return 'ftp';
		}
		
		/**
		 *
		 */
		public function buildAdapter() : void
		{
			$this->_adapter = new FAdapter($this->getConfig());
		}
		
		/**
		 * @return mixed
		 */
		protected function getConfig()
		{
			return FtpConnectionOptions::fromArray($this->_config);
		}
		
		/**
		 * @param array $config
		 */
		public function setConfig( array $config )
		{
			$this->_config = $config;
		}
		
		/**
		 * @return mixed
		 */
		public function getAdapter() : FilesystemAdapter
		{
			return $this->_adapter;
		}
	}
