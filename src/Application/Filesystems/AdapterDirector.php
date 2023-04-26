<?php
	
	
	namespace Marwa\Application\Filesystems;
	
	use League\Flysystem\FilesystemAdapter;
	use Marwa\Application\Filesystems\Adapters\AdapterInterface;
	use Marwa\Application\Filesystems\Exceptions\
	{InvalidAdapterType, InvalidArgumentException
	};
	
	class AdapterDirector {
		
		/**
		 * @var AdapterInterface
		 */
		private $__builder;
		/**
		 * @var array
		 */
		private $_config;
		
		/**
		 * AdapterDirector constructor.
		 *
		 * @param AdapterInterface $builder
		 * @param array $config
		 */
		public function __construct( AdapterInterface $builder, array $config )
		{
			if ( empty($config) )
			{
				throw new InvalidArgumentException('Invalid Configuration');
			}
			
			$this->_config = $config;
			$this->__builder = $builder;
			
		}
		
		/**
		 * @return $this
		 */
		public function build()
		{
			switch ( $this->__builder->getType() )
			{
				case 'local':
					$this->buildLocal();
					break;
				case 'ftp':
					$this->buildFtp();
					break;
				case 'sftp':
					$this->buildSftp();
					break;
				case 's3':
					$this->buildAws();
					break;
				case 'memory':
					$this->buildMemory();
					break;
				default:
					throw new InvalidAdapterType('Invalid Adapter');
			}
			
			return $this;
		}
		
		/**
		 * build local adapter
		 */
		protected function buildLocal() : void
		{
			if ( array_key_exists('path', $this->_config) )
			{
				$this->__builder->setStorage($this->_config['path']);
			}
			
			if ( array_key_exists('visibility', $this->_config) )
			{
				$this->__builder->setVisibility($this->_config['visibility']);
			}
			
			$this->__builder->buildAdapter();
		}
		
		/**
		 *  build ftp adapter
		 */
		protected function buildFtp() : void
		{
			$this->__builder->setConfig($this->_config);
			$this->__builder->buildAdapter();
		}
		
		/**
		 *
		 */
		protected function buildSftp() : void
		{
			$this->__builder->setVisibility($this->_config['visibility']);
			$this->__builder->setHost($this->_config['host']);
			$this->__builder->setPort($this->_config['port']);
			$this->__builder->setUsername($this->_config['username']);
			$this->__builder->setPassword($this->_config['password']);
			$this->__builder->setPath($this->_config['root']);
			$this->__builder->setTimeout($this->_config['timeout']);
			$this->__builder->useAgent($this->_config['useagent']);
			$this->__builder->buildAdapter();
			
		}
		
		/**
		 *
		 */
		protected function buildAws()
		{
			$this->__builder->setKey($this->_config['key']);
			$this->__builder->setSecret($this->_config['secret']);
			$this->__builder->setRegion($this->_config['region']);
			$this->__builder->setBucket($this->_config['bucket']);
			$this->__builder->setVisibility($this->_config['visibility']);
			$this->__builder->setVersion($this->_config['version']);
			$this->__builder->setPrefix($this->_config['prefix']);
			$this->__builder->buildAdapter();
		}
		
		/**
		 *
		 */
		protected function buildMemory() : void
		{
			$this->__builder->buildAdapter();
		}
		
		/**
		 *
		 */
		public function getAdapter() : FilesystemAdapter
		{
			return $this->__builder->getAdapter();
		}
	}
