<?php
	
	
	namespace Marwa\Application\Views;
	
	use Marwa\Application\Utils\Str;
	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;
	
	class TwigExtension extends AbstractExtension {
		
		/**
		 * @var array
		 */
		protected $extensionArray = ['filter' => [], 'function' => []];
		
		/**
		 * TwigExtension constructor.
		 */
		public function __construct()
		{
			$this->loadAllExtensions();
		}
		
		/**
		 *
		 */
		public function loadAllExtensions()
		{
			$this->loadExtensions($this->getVendorViewPath(), 'core');
			$this->loadExtensions($this->getUserExtensionPath(), 'user');
		}
		
		/**
		 * @param string $path
		 * @param string $type
		 * @return bool
		 */
		public function loadExtensions( string $path, string $type = 'core' )
		{
			/**
			 * Read all extensions file from Extensions Folder
			 */
			$extensions = $this->readExtensionsFile($path);
			
			/**
			 * if extensions are empty or is not array then return false
			 */
			if ( empty($extensions) || !is_array($extensions) )
			{
				return false;
			}
			/**
			 * loop through the extensions file
			 */
			foreach ( $extensions as $index => $file )
			{
				/**
				 * extract the file name from the path
				 */
				$extension = pathinfo($file)['filename'];
				
				/**
				 *  If Filename ends with Filter then it will be filter class
				 *  Remove the Filter word and take the rest of word which will be use as filter name
				 *  Method name will be get{$ClassName)
				 *  Same rules apply for functions
				 */
				if ( Str::endsWith($extension, 'Filter') )
				{
					$filterName = strtolower(Str::substring($extension, 0, strlen($extension) - 6));
					if ( strtolower($type) == 'user' )
					{
						$filterClass = 'App\\Extensions\\' . $extension;
					}
					else
					{
						$filterClass = 'Marwa\\Application\\Views\\Extensions\\' . $extension;
						
					}
					array_push($this->extensionArray['filter'], $this->createFilters($filterName, $filterClass, $extension));
					
				}
				else
				{
					if ( Str::endsWith($extension, 'Function') )
					{
						$filterName = strtolower(Str::substring($extension, 0, strlen($extension) - 8));
						if ( strtolower($type) == 'user' )
						{
							$filterClass = 'App\\Extensions\\' . $extension;
						}
						else
						{
							$filterClass = 'Marwa\\Application\\Views\\Extensions\\' . $extension;
							
						}
						array_push($this->extensionArray['function'], $this->createFunctions($filterName, $filterClass, $extension));
					}
				}
				
			}
			
			return true;
		}
		
		/**
		 * @param string $path
		 * @return array|false
		 */
		protected function readExtensionsFile( string $path )
		{
			/**
			 * Read all extensions file from Extensions Folder
			 */
			$extensions = glob($path . '*.php');
			
			/**
			 * if extensions are empty or is not array then return false
			 */
			if ( empty($extensions) || !is_array($extensions) )
			{
				return [];
			}
			
			return $extensions;
		}
		
		/**
		 * @param string $filterName
		 * @param string $filterClass
		 * @param string $method
		 * @return TwigFilter
		 */
		protected function createFilters( string $filterName, string $filterClass, string $method )
		{
			return new TwigFilter($filterName, [new $filterClass(), 'get' . $method]);
		}
		
		/**
		 * @param string $filterName
		 * @param string $filterClass
		 * @param string $method
		 * @return TwigFunction
		 */
		protected function createFunctions( string $filterName, string $filterClass, string $method )
		{
			return new TwigFunction($filterName, [new $filterClass, 'get' . $method]);
		}
		
		/**
		 * @return string
		 */
		public function getVendorViewPath() : string
		{
			return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Extensions' . DIRECTORY_SEPARATOR;
		}
		
		/**
		 * @return string
		 */
		public function getUserExtensionPath() : string
		{
			return base_path() . DIRECTORY_SEPARATOR . 'app'.DIRECTORY_SEPARATOR.'Extensions' . DIRECTORY_SEPARATOR;
		}
		
		/**
		 * @return array|mixed|TwigFilter[]
		 */
		public function getFilters()
		{
			return $this->extensionArray['filter'];
		}
		
		/**
		 * @return array|mixed|TwigFunction[]
		 */
		public function getFunctions()
		{
			return $this->extensionArray['function'];
		}
		
		
	}

