<?php
	
	namespace Marwa\Application\Views;
	
	use Marwa\Application\Views\Interfaces\ViewServiceInterface;
	
	class View {
		
		/**
		 * @var string
		 */
		protected $engine = 'twig';
		
		/**
		 * View constructor.
		 */
		private function __construct()
		{
		
		}
		
		/**
		 * @param array $config
		 * @return ViewServiceInterface
		 */
		public static function getInstance( array $config ) : ViewServiceInterface
		{
			$view = new self();
			if ( isset($config['engine']) )
			{
				$view->engine($config['engine']);
			}
			
			return FactoryView::create($view->getEngine(), $config);
		}
		
		/**
		 * @param string $name
		 */
		public function engine( string $name )
		{
			if ( !empty($name) )
			{
				$this->engine = $name;
				
			}
		}
		
		/**
		 * @return string
		 */
		public function getEngine() : string
		{
			return $this->engine;
		}
	}
