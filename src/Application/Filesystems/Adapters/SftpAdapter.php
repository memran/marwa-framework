<?php
	
	
	namespace Marwa\Application\Filesystems\Adapters;
	
	use League\Flysystem\FilesystemAdapter;
	use League\Flysystem\PHPSecLibV2\SftpAdapter as Sftp;
	use League\Flysystem\PHPSecLibV2\SftpConnectionProvider;
	use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
	
	class SftpAdapter implements AdapterInterface {
		
		/**
		 * @var
		 */
		protected $_adapter;
		
		/**
		 * @var array
		 */
		protected $visibility = [];
		/**
		 * @var string
		 */
		protected $_host;
		/**
		 * @var string
		 */
		protected $_username;
		/**
		 * @var string
		 */
		protected $_password;
		/**
		 * @var int
		 */
		protected $_port;
		/**
		 * @var bool
		 */
		protected $_useAgent;
		/**
		 * @var int
		 */
		protected $_timeout;
		/**
		 * @var string
		 */
		protected $_storage;
		
		/**
		 * @return string
		 */
		public function getType() : string
		{
			return 'sftp';
		}
		
		/**
		 *
		 */
		public function buildAdapter() : void
		{
			$this->_adapter = new Sftp(
				$this->getConfig(),
				$this->getStorage(),
				$this->getVisibility()
			);
		}
		
		/**
		 * @return SftpConnectionProvider
		 */
		protected function getConfig()
		{
			return new SftpConnectionProvider(
				$this->getHost(),
				$this->getUsername(),
				$this->getPassword(),
				$this->getPort(),
				$this->getUseAgent(),
				$this->getTimeout()
			);
		}
		
		/**
		 * @return string
		 */
		protected function getHost() : string
		{
			return $this->_host;
		}
		
		/**
		 * @param string $host
		 */
		public function setHost( string $host ) : void
		{
			$this->_host = $host;
		}
		
		/**
		 * @return string
		 */
		protected function getUsername() : string
		{
			return $this->_username;
		}
		
		/**
		 * @param string $username
		 */
		public function setUsername( string $username ) : void
		{
			$this->_username = $username;
		}
		
		/**
		 * @return string
		 */
		protected function getPassword() : string
		{
			return $this->_password;
		}
		
		/**
		 * @param string $password
		 */
		public function setPassword( string $password ) : void
		{
			$this->_password = $password;
		}
		
		/**
		 * @return int
		 */
		protected function getPort() : int
		{
			return $this->_port;
		}
		
		/**
		 * @param int $port
		 */
		public function setPort( int $port )
		{
			$this->_port = $port;
		}
		
		/**
		 * @return bool
		 */
		protected function getUseAgent() : bool
		{
			return $this->_useAgent;
		}
		
		/**
		 * @param bool $useAgent
		 */
		public function setUseAgent( bool $useAgent ) : void
		{
			$this->_useAgent = $useAgent;
		}
		
		/**
		 * @return int
		 */
		protected function getTimeout() : int
		{
			return $this->_timeout;
		}
		
		/**
		 * @param int $timeout
		 */
		public function setTimeout( int $timeout ) : void
		{
			$this->_timeout = $timeout;
		}
		
		protected function getStorage() : string
		{
			return $this->_storage;
		}
		
		public function setStorage( string $path ) : void
		{
			$this->_storage = $path;
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
			$this->visibility = $visibility;
		}
		
		/**
		 * @return mixed
		 */
		public function getAdapter() : FilesystemAdapter
		{
			return $this->_adapter;
		}
	}
