<?php
	
	
	namespace Marwa\Application\Filesystems\Adapters;
	
	use Aws\S3\S3Client;
	use League\Flysystem\AwsS3V3\AwsS3V3Filesystem;
	use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
	use League\Flysystem\FilesystemAdapter;
	use League\Flysystem\Visibility;
	
	
	class AwsAdapter implements AdapterInterface {
		
		/**
		 * @var FilesystemAdapter
		 */
		protected $_adapter;
		/**
		 * @var string
		 */
		protected $_visible;
		/**
		 * @var S3Client
		 */
		protected $_client;
		/**
		 * @var string
		 */
		protected $_key;
		/**
		 * @var string
		 */
		protected $_secret;
		/**
		 * @var string
		 */
		protected $_region;
		/**
		 * @var string
		 */
		protected $_version;
		/**
		 * @var string
		 */
		protected $_bucket;
		/**
		 * @var string
		 */
		protected $_prefix;
		
		/**
		 * @param string $visible
		 */
		public function setVisibility( string $visible ) : void
		{
			$this->_visible = strtoupper($visible);
		}
		
		/**
		 *
		 */
		public function buildAdapter() : void
		{
			$this->_adapter = new AwsS3V3Filesystem(
				$this->getClient(),
				$this->getBucket(),
				$this->getPrefix(),
				$this->getVisibility()
			);
		}
		
		/**
		 * @return S3Client
		 */
		public function getClient()
		{
			return new S3Client(
				$this->getCrediential()
			);
		}
		
		/**
		 * @return array
		 */
		protected function getCrediential() : array
		{
			return [
				'credentials' => [
					'key' => $this->getKey(),
					'secret' => $this->getSecret(),
				],
				'region' => $this->getRegion(),
				'version' => $this->getVersion(),
			];
		}
		
		/**
		 * @return string
		 */
		protected function getKey() : string
		{
			return $this->_key;
		}
		
		/**
		 * @param string $key
		 */
		public function setKey( string $key ) : void
		{
			$this->_key = $key;
		}
		
		/**
		 * @return string
		 */
		protected function getSecret() : string
		{
			return $this->_secret;
		}
		
		/**
		 * @param string $secret
		 */
		public function setSecret( string $secret ) : void
		{
			$this->_secret = $secret;
		}
		
		/**
		 * @return string
		 */
		protected function getRegion() : string
		{
			return $this->_region;
		}
		
		/**
		 * @param string $region
		 */
		public function setRegion( string $region ) : void
		{
			$this->_region = $region;
		}
		
		/**
		 * @return string
		 */
		protected function getVersion() : string
		{
			return $this->_version;
		}
		
		/**
		 * @param string $version
		 */
		public function setVersion( string $version ) : void
		{
			$this->_version = $version;
		}
		
		/**
		 * @return string
		 */
		protected function getBucket() : string
		{
			return $this->_bucket;
		}
		
		/**
		 * @param string $bucket
		 */
		public function setBucket( string $bucket ) : void
		{
			$this->_bucket = $bucket;
		}
		
		/**
		 * @return string
		 */
		protected function getPrefix() : string
		{
			return $this->_prefix;
		}
		
		/**
		 * @param string $prefix
		 */
		public function setPrefix( string $prefix ) : void
		{
			$this->_prefix = $prefix;
		}
		
		/**
		 * @return PortableVisibilityConverter
		 */
		public function getVisibility()
		{
			if ( $this->_visible === 'PUBLIC' )
			{
				return new PortableVisibilityConverter(Visibility::PUBLIC);
			}
			
			return new PortableVisibilityConverter(Visibility::PRIVATE);
		}
		
		/**
		 * @return mixed
		 */
		public function getAdapter() : FilesystemAdapter
		{
			return $this->_adapter;
		}
		
		/**
		 * @return string
		 */
		public function getType() : string
		{
			return 's3';
		}
	}
